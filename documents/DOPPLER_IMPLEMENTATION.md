# Doppler Implementation Guide (For Beginners)

This guide will teach you how to securely manage your Stripe Secret Key (and other sensitive credentials) using **Doppler**. Doppler is a "SecretOps" platform that acts as a secure vault for your environment variables. Instead of hardcoding your passwords or API keys in your code, you store them in Doppler, and Doppler injects them into your application when it runs.

Since your project uses vanilla PHP (like XAMPP or a local server) without a framework, we will cover the easiest and most secure way to integrate it.

---

## Part 1: Setting up Doppler

### Step 1: Create a Doppler Account
1. Go to [doppler.com](https://www.doppler.com/) and create a free account.
2. Once logged in, click **Create Project**.
3. Name your project `elitedrive` (or anything you prefer).

### Step 2: Add your Stripe Secret Key
1. In your new Doppler project, you will see environments like `dev`, `stg`, and `prd`. Click on the **dev** (Development) environment.
2. Click **Add Secret**.
3. For the **Name**, type `STRIPE_SECRET_KEY`.
4. For the **Value**, paste your actual Stripe Secret Key (it usually starts with `sk_test_...`).
5. Click **Save**.

*(You can also move your database credentials here like `DB_PASS`, `DB_USER` etc. later!)*

---

## Part 2: Installing Doppler on Your Computer

To let your local computer talk to Doppler, you need to install the Doppler CLI (Command Line Interface).

### For Windows:
Open PowerShell as Administrator and run:
```powershell
(Invoke-WebRequest -useb https://cli.doppler.com/install.ps1) | Invoke-Expression
```

### For macOS:
Open Terminal and run:
```bash
brew install dopplerhq/cli/doppler
```

### Log In to Doppler
Open your terminal (Command Prompt or PowerShell) inside your project folder (`d:\Pamith\Web project Y2S1\Vehical_rental_system`) and run:
```bash
doppler login
```
This will open your web browser. Click **Authorize** to link your terminal to your Doppler account.

### Connect Your Project
Still in your terminal, run:
```bash
doppler setup
```
- It will ask which project to select. Choose `elitedrive`.
- It will ask which environment to select. Choose `dev`.

---

## Part 3: Modifying Your PHP Code

Currently, your application reads credentials from `config/config.php`. We need to update this file to look for environment variables provided by Doppler.

Open `config/config.php` and update it so it checks for the `STRIPE_SECRET_KEY` environment variable using PHP's built-in `getenv()` function:

```php
<?php
// config/config.php
return [
    'db_host' => 'localhost',         
    'db_name' => 'elitedrive',        
    'db_user' => 'root',  
    'db_pass' => '',  
    'base_url' => 'http://localhost/Vehical_rental_system/public', 
    'commission_pct' => 15.00,
    
    // 👇 Add this line to securely fetch the Stripe key from Doppler
    'stripe_secret_key' => getenv('STRIPE_SECRET_KEY') ?: 'fallback_if_not_found',
];
```

---

## Part 4: Running Your Application with Doppler

Since you are not using a framework, you likely run your site using XAMPP/WAMP (Apache) or PHP's built-in server. 

### Method A: Using PHP's Built-in Server (Recommended for Doppler)
The easiest way to let Doppler inject the secrets into PHP is to start the PHP server **through** Doppler. In your terminal, run:
```bash
doppler run -- php -S localhost:8000 -t public
```
*What this does:* Doppler temporarily loads your secrets into the environment and immediately starts the PHP server. When you visit `http://localhost:8000`, PHP's `getenv('STRIPE_SECRET_KEY')` will magically contain your Stripe key!

### Method B: Using XAMPP / Apache
If you use XAMPP, Doppler cannot easily inject secrets directly into the background Apache service. Instead, you can use Doppler to automatically generate your configuration file!

1. Change your `config.php` to look like this:
```php
<?php
return [
    'stripe_secret_key' => '$_DOPPLER_STRIPE_SECRET_KEY', // Doppler will replace this
];
```
2. Save this file as `config.template.php` instead of `config.php`.
3. Whenever you change a secret in Doppler, run this command to generate your actual `config.php`:
```bash
doppler secrets substitute config/config.template.php > config/config.php
```
*What this does:* It downloads your secrets securely from the cloud and injects them into the `config.php` file so XAMPP can read it normally. Make sure `config.php` is in your `.gitignore` so you don't commit it to GitHub!

---

## Summary
1. Store secrets securely in the Doppler Web Dashboard.
2. Connect your local folder to Doppler using `doppler setup`.
3. Use `doppler run` or `doppler secrets substitute` to securely pass those secrets into your PHP application.
