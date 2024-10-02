<?php

namespace YourVendor\FixTranslators;

use GuzzleHttp\Client;

class FixTranslators {
    private $client;
    private $apiKey;

    public function __construct($apiKey) {
        $this->client = new Client();
        $this->apiKey = $apiKey;
    }

    // Function to interact with the OpenAI API
    public function getCommentFromChatGPT($snippet) {
        $prompt = "Provide a suitable translators comment for this code: " . $snippet;

        try {
            $response = $this->client->post('https://api.openai.com/v1/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'text-davinci-003',
                    'prompt' => $prompt,
                    'max_tokens' => 60,
                    'temperature' => 0.5,
                ]
            ]);

            $responseBody = json_decode($response->getBody(), true);
            return $responseBody['choices'][0]['text'];

        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage();
            return null;
        }
    }

    // Function to add translators' comments to files
    public function addTranslatorsComment($filePath) {
        $content = file_get_contents($filePath);

        // Regex to match __() and sprintf() calls
        $pattern = '/(sprintf\s*\(\s*__\(\s*\'(.*?)\'.*?\)\s*,\s*(.*?))\)/';

        $content = preg_replace_callback($pattern, function ($matches) {
            $codeSnippet = $matches[0];
            $comment = $this->getCommentFromChatGPT($codeSnippet);

            if ($comment) {
                return "/* translators: $comment */\n" . $matches[0];
            } else {
                return $matches[0];
            }
        }, $content);

        file_put_contents($filePath, $content);
    }

    // Function to recursively process directory
    public function processDirectory($dir) {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $filePath = $dir . '/' . $file;
            if (is_dir($filePath)) {
                $this->processDirectory($filePath);
            } elseif (pathinfo($filePath, PATHINFO_EXTENSION) === 'php') {
                $this->addTranslatorsComment($filePath);
            }
        }
    }
}
