<?php
namespace tuhin18003\FixTranslators;

use GuzzleHttp\Client;

class FixTranslators
{
    private $client;
    private $apiKey;
    private $resultSummary = [];

    public function __construct($apiKey)
    {
        $this->client = new Client();
        $this->apiKey = $apiKey;
    }

    /**
     * Function to interact with the OpenAI API
     *
     * @param [type] $snippet
     * @return void
     */
    public function getCommentFromChatGPT($snippet)
    {
        $prompt = "Provide a suitable translators comment for this code: " . $snippet;

        // return " %s is the rule title."; // Example static return for testing

        try {
            $response = $this->client->post(
                'https://api.openai.com/v1/chat/completions',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Content-Type'  => 'application/json',
                    ],
                    'json'    => [
                        'model'       => 'gpt-3.5-turbo',
                        'messages'    => [
                            // Optional system instruction
                            ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                            // Your user prompt
                            ['role' => 'user', 'content' => $prompt],
                        ],
                        'max_tokens'  => 60,
                        'temperature' => 0.5,
                    ],
                ]
            );

            $responseBody = json_decode($response->getBody(), true);

            // 1️⃣ Completions-style response
            if (! empty($responseBody['choices'][0]['text'])) {
                return $responseBody['choices'][0]['text'];
            }
            // 2️⃣ Chat-style response
            elseif (! empty($responseBody['choices'][0]['message']['content'])) {
                return $responseBody['choices'][0]['message']['content'];
            }
            // 3️⃣ Nothing usable
            else {
                echo 'No valid response from OpenAI API.';
                return null;
            }

        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage();
            return null;
        }
    }

    /**
     * Function to add translators' comments to files
     *
     * @param [type] $filePath
     * @return void
     */
    public function addTranslatorsComment($filePath)
    {
        $content = file_get_contents($filePath);

                      // Initialize counters
        $matches = 0; // Total matches found
        $fixed   = 0; // Newly added comments

        // Regex to match sprintf and __() calls
        $pattern = '/(sprintf\s*\(\s*__\(\s*\'(.*?)\'.*?\)\s*,\s*(.*?))\)/';

        // Split content into lines for individual processing
        $lines         = explode("\n", $content);
        $newContent    = '';
        $commentExists = 0;

        foreach ($lines as $i => $line) {
            // Check if the line contains a sprintf call
            if (preg_match($pattern, $line, $matchesArray)) {
                $matches++; // Increment matches count

                // Check if the previous line already contains a Translators comment
                $previousLineIndex = $i - 1;
                $alreadyHasComment = false;

                // Check for existing translators comment in the previous line
                if ($previousLineIndex >= 0) {
                    $previousLine = trim($lines[$previousLineIndex]);
                    if (strpos($previousLine, '/* Translators:') !== false) {
                        $alreadyHasComment = true;
                    }
                }

                // If the comment already exists, treat it as fixed without adding a new one
                if ($alreadyHasComment) {
                    // Do not increment fixed, but treat as fixed for remaining count
                    $commentExists++;
                } else {
                    // Generate a new comment only if it doesn't exist
                    $comment = $this->getCommentFromChatGPT($matchesArray[0]);

                    if ($comment) {
                                  // If a new comment is generated, add it
                        $fixed++; // Increment fixed count since we're adding a new comment

                        // Get leading whitespace from the current line
                        $leadingWhitespace = '';
                        preg_match('/^\s*/', $line, $whitespaceMatches);
                        if (! empty($whitespaceMatches)) {
                            $leadingWhitespace = $whitespaceMatches[0]; // Capture leading whitespace
                        }

                                                                                             // Add the comment with the same indentation as the line
                        $newContent .= $leadingWhitespace . "/* Translators: $comment */\n"; // Add the comment with leading whitespace
                    }

                    // Append the original line
                    $newContent .= $line . "\n";
                }
            } else {
                // If there's no match, just append the original line
                $newContent .= $line . "\n";
            }
        }

        // Write the modified content back if any new comments were added
        if ($fixed > 0) {
            file_put_contents($filePath, $newContent);
        }

        // Calculate remaining count based on matches and fixed comments
        $remaining = $matches - ($fixed + $commentExists);

        // Ensure remaining count does not go negative
        if ($remaining < 0) {
            $remaining = 0;
        }

        // Track summary information for the result
        $this->resultSummary[] = [
            'file'      => $filePath,
            'fixed'     => $fixed,     // Count of newly added comments
            'remaining' => $remaining, // Remaining count adjusted correctly
        ];
    }

    /**
     * Function to recursively process directory
     *
     * @param [type] $input
     * @return void
     */
    public function processDirectory($input)
    {
        // Check if the input is a directory
        if (is_dir($input)) {
            $files = scandir($input);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                // Skip current and parent directory indicators
                $filePath = $input . '/' . $file;
                if (is_dir($filePath)) {
                    // Recursively process subdirectories
                    $this->processDirectory($filePath);
                } elseif (pathinfo($filePath, PATHINFO_EXTENSION) === 'php') {
                    // Process PHP files
                    $this->addTranslatorsComment($filePath);
                }
            }
        } elseif (is_file($input) && pathinfo($input, PATHINFO_EXTENSION) === 'php') {
            // If the input is a single PHP file, process it directly
            $this->addTranslatorsComment($input);
        } else {
            // Handle the case where the input is neither a valid directory nor a PHP file
            echo "The provided input is not a valid PHP file or directory: $input\n";
        }
    }

    /**
     * Function to display the result summary
     *
     * @return void
     */
    public function displayResultSummary()
    {
        echo "\nTRANSLATORS RESULT SUMMARY\n";
        echo "--------------------------------------------------------------------------------\n";
        echo "FILE                                                            FIXED  REMAINING\n";
        echo "--------------------------------------------------------------------------------\n";

        $totalFixed     = 0;
        $totalRemaining = 0;

        foreach ($this->resultSummary as $result) {
            $fileName  = $result['file'];
            $fixed     = $result['fixed'];
            $remaining = $result['remaining'];
            $totalFixed += $fixed;
            $totalRemaining += $remaining;

            // Shorten file path for better readability in summary
            $shortFileName = substr($fileName, -50);
            printf("%-60s %6d %10d\n", $shortFileName, $fixed, $remaining);
        }

        echo "--------------------------------------------------------------------------------\n";
        printf("A TOTAL OF %d TRANSLATORS COMMENTS WERE FIXED IN %d FILES\n", $totalFixed, count($this->resultSummary));
        echo "--------------------------------------------------------------------------------\n";
    }
}
