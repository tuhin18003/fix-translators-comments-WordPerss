# Fix Translators Comments

This package automatically adds translators' comments to your WordPress localization strings using ChatGPT.

## Installation

Run the following command to install via Composer:

```bash
composer require your-vendor/fix-translators
```

## Usage

Once the package is installed, you can run the command to fix translators' comments in your WordPress localization files.

## Command

You can run the command using Composer as follows:

```bash
composer run-script fix-translators-comments --openApiKey=<YOUR_API_KEY> --directory=<DIRECTORY_PATH>
```

## Parameters
*  openApiKey: Your OpenAI API key to authenticate requests.
*  directory: The path to the directory containing the files you want to process.

## Example Usages

**1.  **Using a specific API key and directory: ****

If your OpenAI API key is `exampleApiKey` and you want to process files in the `/test` directory, the command will look like this:

```bash 
composer run-script fix-translators-comments --openApiKey=exampleApiKey --directory=/test
```

**2. Processing files in the current directory:**

If you want to process files in the current directory, you can simply run:

```bash 
composer run-script fix-translators-comments --openApiKey=exampleApiKey --directory=.
```
**3. Checking the usage:**

If you need to check the command usage and parameters, you can use:

```bash 
composer run-script fix-translators-comments
```


### Credentials
- *Created by - [M.Tuhin](https://codesolz.net/)*

<a href="https://codesolz.net">
  <img src="https://codesolz.net/images/brand-logo/logo.png" alt="codesolz.net"/>
</a>
