# LCRO Smart Contract Document Validation System

Local deployment guide for the LGU Magallanes PC.

Target deployment date: Monday, May 18, 2026.

This guide is for a single Windows PC only. No cloud deployment is required. The scanner connects through USB. The browser opens the Laravel system at `http://127.0.0.1:8000`.

## 1. Important Project-Specific Notes

Based on the current codebase:

- Laravel requires PHP `^8.2` from `composer.json`.
- Laravel uses MySQL through XAMPP.
- Queue jobs use `QUEUE_CONNECTION=database`.
- Paddle OCR runs locally at `http://127.0.0.1:8001`.
- Scanner bridge runs locally at `http://127.0.0.1:3000`.
- Ganache runs locally at `http://127.0.0.1:7545`.
- The current document anchoring path uses `App\Services\GanacheBlockchainService`.
- Current anchoring sends a real Ganache transaction from the Ganache account back to the same account, with document metadata/hash stored in the transaction input data.
- `BLOCKCHAIN_CONTRACT_ADDRESS` exists in `.env`, but the current document anchoring service does not use it. If your team has a separate smart contract/deployment folder outside this project, bring it. Otherwise, do not block local deployment just because `BLOCKCHAIN_CONTRACT_ADDRESS` is blank.

## 2. What To Prepare Before Going To LGU

Prepare one USB or external drive with this structure:

```text
LCRO-DEPLOYMENT\
  final_capstone\
  scanner-bridge\
  database-backup\
  installers\
  backups\
  notes\
```

Bring these files/folders:

- `C:\Users\PC\final_capstone`
- `C:\Users\PC\scanner-bridge`
- latest full database SQL backup, for example `final_capstone.sql`
- latest uploaded document files, especially `storage\app\public`
- `.env` backup from your laptop
- admin/staff/supervisor login credentials for testing
- scanner driver installer
- XAMPP installer, 64-bit, with PHP 8.2 or newer
- Composer installer for Windows
- Node.js LTS installer, 64-bit
- Python installer, recommended Python 3.12 64-bit for Paddle OCR stability
- Ganache GUI installer
- optional but recommended: Tesseract OCR installer, because Laravel has a Tesseract fallback if Paddle OCR fails
- optional offline dependency backups: `vendor`, `node_modules`, `paddle_ocr_service\venv`, Python wheelhouse

Recommended backup commands from your laptop:

```bat
mkdir E:\LCRO-DEPLOYMENT
mkdir E:\LCRO-DEPLOYMENT\database-backup
mkdir E:\LCRO-DEPLOYMENT\installers
mkdir E:\LCRO-DEPLOYMENT\backups
mkdir E:\LCRO-DEPLOYMENT\notes

robocopy C:\Users\PC\final_capstone E:\LCRO-DEPLOYMENT\final_capstone /E
robocopy C:\Users\PC\scanner-bridge E:\LCRO-DEPLOYMENT\scanner-bridge /E
cmd /c ""C:\xampp\mysql\bin\mysqldump.exe" -u root final_capstone > "E:\LCRO-DEPLOYMENT\database-backup\final_capstone.sql""
```

If the SQL backup is already in `C:\Users\PC\final_capstone\database\final_capstone.sql`, still create a fresh export before leaving.

Before leaving home, test the deployment from a clean path on your own laptop if possible:

```bat
mkdir C:\LCRO-System
robocopy E:\LCRO-DEPLOYMENT\final_capstone C:\LCRO-System\final_capstone /E
robocopy E:\LCRO-DEPLOYMENT\scanner-bridge C:\LCRO-System\scanner-bridge /E
```

## 3. Should You Copy Installed XAMPP Or Ganache Folders?

Safer option: install them properly on the LGU PC.

Do not depend on copying installed program folders such as `C:\xampp` or Ganache installation folders. Installed programs can depend on Windows registry entries, services, firewall rules, PATH settings, user profile data, and internal absolute paths.

What can be copied:

- your Laravel project folder
- scanner bridge folder
- SQL backup
- uploaded document files
- `.env` template or backup
- `vendor`, `node_modules`, and `venv` only as emergency offline fallback

What should be installed:

- XAMPP
- Composer
- Node.js
- Python
- Ganache GUI
- scanner driver
- Tesseract OCR, if you want fallback OCR safety

Portable XAMPP can work, but the normal installer is safer for students because the Control Panel, MySQL path, and PHP path are clearer. Use portable XAMPP only if the LGU PC blocks installation.

Ganache GUI should be installed fresh. Copying only the Ganache app folder is not enough to preserve blockchain data. Ganache workspace/blockchain data is separate from the application installation.

## 4. Recommended LGU Folder Layout

Create:

```text
C:\LCRO-System\
  final_capstone\
  scanner-bridge\
  database-backup\
  installers\
  backups\
```

Copy your prepared files:

```bat
mkdir C:\LCRO-System
robocopy E:\LCRO-DEPLOYMENT\final_capstone C:\LCRO-System\final_capstone /E
robocopy E:\LCRO-DEPLOYMENT\scanner-bridge C:\LCRO-System\scanner-bridge /E
robocopy E:\LCRO-DEPLOYMENT\database-backup C:\LCRO-System\database-backup /E
robocopy E:\LCRO-DEPLOYMENT\installers C:\LCRO-System\installers /E
mkdir C:\LCRO-System\backups
```

Set the LGU PC power settings to prevent interruption:

- Settings
- System
- Power
- Screen and sleep
- Set sleep to Never while plugged in

## 5. Install And Verify Required Tools

Install XAMPP to:

```text
C:\xampp
```

Start XAMPP Control Panel and start:

- Apache
- MySQL

Verify PHP:

```bat
php -v
```

If `php` is not recognized:

```bat
C:\xampp\php\php.exe -v
```

Add this to Windows PATH if needed:

```text
C:\xampp\php
```

Verify PHP extensions:

```bat
php -m | findstr /I "pdo_mysql fileinfo gd mbstring openssl zip"
```

Install Composer. When asked for PHP path, select:

```text
C:\xampp\php\php.exe
```

Verify Composer:

```bat
composer --version
```

If global Composer fails but `composer.phar` exists in the project:

```bat
cd /d C:\LCRO-System\final_capstone
php composer.phar --version
```

Install Node.js LTS 64-bit and verify:

```bat
node -v
npm -v
```

Install Python 3.12 64-bit. During installation, check:

```text
Add python.exe to PATH
```

Verify:

```bat
python --version
py -3.12 --version
```

Install Ganache GUI. Open it once and confirm it starts.

Install the scanner driver. Test the scanner in Windows first before testing the web system.

Optional but recommended: install Tesseract OCR to:

```text
C:\Program Files\Tesseract-OCR\tesseract.exe
```

Verify:

```bat
tesseract --version
```

## 6. Import The Database

Start XAMPP Apache and MySQL.

Open:

```text
http://127.0.0.1/phpmyadmin
```

In phpMyAdmin:

1. Click Databases.
2. Create database:

```text
final_capstone
```

3. Use collation:

```text
utf8mb4_unicode_ci
```

4. Click the new database.
5. Click Import.
6. Select:

```text
C:\LCRO-System\database-backup\final_capstone.sql
```

7. Click Go.

Command-line alternative:

```bat
"C:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS final_capstone CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
cmd /c ""C:\xampp\mysql\bin\mysql.exe" -u root final_capstone < "C:\LCRO-System\database-backup\final_capstone.sql""
```

If MySQL root has a password, use:

```bat
"C:\xampp\mysql\bin\mysql.exe" -u root -p
```

Important: if you import old records that already have old Ganache transaction hashes, those old transaction hashes will not exist on a fresh Ganache chain. For final deployment, prove blockchain anchoring by scanning/saving a new document on the LGU PC and showing the new transaction in Ganache.

## 7. Configure Laravel `.env`

Go to:

```bat
cd /d C:\LCRO-System\final_capstone
```

If `.env` is missing:

```bat
copy .env.example .env
php artisan key:generate
```

Recommended local values:

```dotenv
APP_NAME="LCRO System"
APP_ENV=local
APP_KEY=base64:KEEP_OR_GENERATE_THIS
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=final_capstone
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database
FILESYSTEM_DISK=local

BLOCKCHAIN_ENABLED=true
BLOCKCHAIN_PROVIDER=http://127.0.0.1:7545
BLOCKCHAIN_NETWORK_ID=5777
BLOCKCHAIN_CHAIN_ID=5777
BLOCKCHAIN_ACCOUNT_ADDRESS=PASTE_GANACHE_ACCOUNT_ADDRESS_HERE
BLOCKCHAIN_PRIVATE_KEY=PASTE_PRIVATE_KEY_HERE_IF_USED
BLOCKCHAIN_CONTRACT_ADDRESS=
BLOCKCHAIN_GAS_LIMIT=6721975
BLOCKCHAIN_GAS_PRICE=20000000000
BLOCKCHAIN_TIMEOUT=30
BLOCKCHAIN_RETRY_ATTEMPTS=3

GANACHE_URL=http://127.0.0.1:7545
GANACHE_NETWORK_ID=5777
GANACHE_FROM_ADDRESS=PASTE_GANACHE_ACCOUNT_ADDRESS_HERE
GANACHE_GAS_LIMIT=6721975
GANACHE_TIMEOUT=30

SCANNER_BRIDGE_URL=http://127.0.0.1:3000
SCANNER_BRIDGE_ENABLED=true

PADDLE_OCR_URL=http://127.0.0.1:8001
PADDLE_OCR_TIMEOUT=120
PADDLE_OCR_DISABLE_FALLBACK=true
```

Use `APP_DEBUG=true` while students are setting up and testing. After everything works, you may set it to `false`.

After any `.env` change:

```bat
cd /d C:\LCRO-System\final_capstone
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan queue:restart
```

Then close and restart:

- `php artisan serve`
- `php artisan queue:work`

Reason: Laravel reads `.env` at startup. Long-running command windows do not automatically reload changed `.env` values.

## 8. Ganache Configuration Explained

Ganache GUI is the desktop app that runs the local blockchain.

RPC URL is the local address Laravel uses to talk to Ganache:

```text
http://127.0.0.1:7545
```

Ganache account address is the wallet-like address that sends transactions:

```text
0x...
```

Private key is the secret key for that account. Do not post it online or include it in screenshots. In your current document anchoring code, Ganache's unlocked local account is enough for `eth_sendTransaction`, but keep `BLOCKCHAIN_PRIVATE_KEY` correct if future code uses signed transactions.

Smart contract address is the address of a deployed contract. In a fresh Ganache chain, old contract addresses from another machine usually do not work because the chain state is different.

Laravel queue worker is the command that processes background jobs:

```bat
php artisan queue:work --verbose --tries=3 --timeout=300
```

Blockchain anchoring means saving a document hash/metadata into a Ganache transaction so later the system can show a transaction hash, block number, sender, gas, and timestamp metadata.

## 9. Fresh Ganache Setup On LGU PC

Open Ganache GUI.

Use Quickstart Ethereum or create a workspace with:

```text
Host: 127.0.0.1
Port: 7545
Network ID: 5777
Chain ID: 5777
Automine: enabled
```

Get the RPC URL:

```text
http://127.0.0.1:7545
```

Get the account address:

1. In Ganache, go to Accounts.
2. Copy the first account address.
3. Paste it into:

```dotenv
GANACHE_FROM_ADDRESS=0x...
BLOCKCHAIN_ACCOUNT_ADDRESS=0x...
```

Get the private key:

1. In Ganache Accounts, click the key icon beside the same first account.
2. Copy the private key.
3. Paste it into:

```dotenv
BLOCKCHAIN_PRIVATE_KEY=...
```

For the current codebase, document anchoring does not require `BLOCKCHAIN_CONTRACT_ADDRESS`. Leave it blank unless you deploy a real contract and update the code to use that contract.

If your team has a separate smart contract project:

1. Start Ganache first.
2. Deploy the contract to the LGU Ganache chain.
3. Copy the new deployed contract address.
4. Paste it into `BLOCKCHAIN_CONTRACT_ADDRESS`.
5. Run `php artisan optimize:clear`.
6. Restart Laravel and queue worker.

Test Ganache RPC:

```powershell
Invoke-RestMethod -Uri http://127.0.0.1:7545 -Method Post -ContentType "application/json" -Body '{"jsonrpc":"2.0","method":"eth_blockNumber","params":[],"id":1}'
```

Test accounts:

```powershell
Invoke-RestMethod -Uri http://127.0.0.1:7545 -Method Post -ContentType "application/json" -Body '{"jsonrpc":"2.0","method":"eth_accounts","params":[],"id":1}'
```

Test from Laravel:

```bat
cd /d C:\LCRO-System\final_capstone
php artisan tinker
```

Inside Tinker:

```php
app(\App\Services\GanacheBlockchainService::class);
```

If there is no error, Laravel can connect to Ganache.

## 10. How To Search Blockchain Variables In The Codebase

Use this in the Laravel project:

```bat
cd /d C:\LCRO-System\final_capstone
rg -n "GANACHE|WEB3|RPC|CONTRACT|PRIVATE_KEY|WALLET|CHAIN|BLOCKCHAIN" app config routes resources
```

Important files found in this codebase:

```text
config\blockchain.php
app\Services\GanacheBlockchainService.php
app\Jobs\AnchorToBlockchainJob.php
app\Models\Scan.php
app\Http\Controllers\ScanController.php
```

If `rg` is not installed:

```bat
findstr /S /N /I "GANACHE WEB3 RPC CONTRACT PRIVATE_KEY WALLET CHAIN BLOCKCHAIN" app\*.php config\*.php routes\*.php
```

## 11. Can You Copy Existing Ganache Blockchain Data?

Possible, but not recommended for capstone deployment day.

Typical Windows Ganache data is under:

```text
C:\Users\<username>\AppData\Roaming\Ganache
```

Some workspace data may also be stored in a workspace/database folder chosen inside Ganache settings.

What can go wrong:

- Ganache version mismatch
- corrupted workspace database
- copied app but not copied chain data
- old transaction hashes in MySQL do not exist on the new chain
- old account address has no funds or is not unlocked
- old contract address points to nothing on fresh Ganache
- absolute paths in workspace settings break

Safer option:

1. Install Ganache fresh.
2. Create a new workspace.
3. Copy first account address.
4. Update `.env`.
5. Run `php artisan optimize:clear`.
6. Restart Laravel and queue worker.
7. Scan/save a new document and confirm a new transaction appears in Ganache.

If you need old imported records to match old Ganache transactions, then you must copy the old Ganache chain state successfully. For a student deployment/demo, the safer proof is to create new LGU transactions on the LGU PC.

## 12. Install Laravel Dependencies

Open Command Prompt:

```bat
cd /d C:\LCRO-System\final_capstone
composer install
npm install
npm run build
php artisan storage:link
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

If this is a clean `.env`:

```bat
php artisan key:generate
```

If the SQL backup is old and the project has newer migrations, run:

```bat
php artisan migrate --force
```

Do not run `migrate:fresh` on the LGU PC unless you intentionally want to erase database data.

## 13. Install Scanner Bridge Dependencies

```bat
cd /d C:\LCRO-System\scanner-bridge
npm install
node server.js
```

Keep this window open.

Test:

```text
http://127.0.0.1:3000/health
http://127.0.0.1:3000/scanners
```

Important: the current frontend has direct calls to `http://127.0.0.1:3000`, so keep the scanner bridge on port `3000`.

## 14. Set Up Paddle OCR Service

Open Command Prompt:

```bat
cd /d C:\LCRO-System\final_capstone\paddle_ocr_service
py -3.12 -m venv venv
venv\Scripts\activate
python -m pip install --upgrade pip
pip install -r requirements.txt
uvicorn main:app --host 127.0.0.1 --port 8001
```

If `py -3.12` does not work:

```bat
python -m venv venv
venv\Scripts\activate
pip install -r requirements.txt
uvicorn main:app --host 127.0.0.1 --port 8001
```

If `venv` already exists:

```bat
cd /d C:\LCRO-System\final_capstone\paddle_ocr_service
venv\Scripts\activate
uvicorn main:app --host 127.0.0.1 --port 8001
```

Test:

```text
http://127.0.0.1:8001/health
```

PowerShell test:

```powershell
Invoke-RestMethod http://127.0.0.1:8001/health
```

First run can be slow because Paddle OCR loads or downloads models. If the LGU has no internet, prepare Python wheels and model cache before deployment, or bring a tested fallback copy of `paddle_ocr_service\venv`.

Offline wheel preparation from laptop:

```bat
cd /d C:\Users\PC\final_capstone\paddle_ocr_service
mkdir E:\LCRO-DEPLOYMENT\installers\python-wheels
pip download -r requirements.txt -d E:\LCRO-DEPLOYMENT\installers\python-wheels
```

Offline install on LGU:

```bat
cd /d C:\LCRO-System\final_capstone\paddle_ocr_service
py -3.12 -m venv venv
venv\Scripts\activate
pip install --no-index --find-links C:\LCRO-System\installers\python-wheels -r requirements.txt
```

## 15. Manual Run Order

Use this order first. Do not create the BAT shortcut until this works manually.

Step 1: Start XAMPP

- Open XAMPP Control Panel.
- Start Apache.
- Start MySQL.

Step 2: Open Ganache GUI

- Open the LGU workspace or Quickstart.
- Confirm RPC is `http://127.0.0.1:7545`.

Step 3: Run Laravel

```bat
cd /d C:\LCRO-System\final_capstone
php artisan serve --host=127.0.0.1 --port=8000
```

Step 4: Run Paddle OCR

```bat
cd /d C:\LCRO-System\final_capstone\paddle_ocr_service
venv\Scripts\activate
uvicorn main:app --host 127.0.0.1 --port 8001
```

Step 5: Run blockchain queue worker

```bat
cd /d C:\LCRO-System\final_capstone
php artisan queue:work --verbose --tries=3 --timeout=300
```

Step 6: Run scanner bridge

```bat
cd /d C:\LCRO-System\scanner-bridge
node server.js
```

Step 7: Open the browser

```text
http://127.0.0.1:8000
```

## 16. Test Blockchain Anchoring

The normal test:

1. Open Laravel at `http://127.0.0.1:8000`.
2. Log in as staff/admin.
3. Scan or upload a test document.
4. Apply OCR if required.
5. Complete required fields.
6. Save the document.
7. Watch the queue worker window.
8. Watch Ganache transactions.
9. Confirm the document shows `blockchain_status=confirmed` and has a transaction hash.

Database check:

```bat
cd /d C:\LCRO-System\final_capstone
php artisan tinker
```

Inside Tinker:

```php
\App\Models\Scan::latest()->first(['id','document_id','blockchain_status','blockchain_tx_hash','blockchain_block_number']);
```

Check queued jobs:

```bat
php artisan queue:work --once --verbose --tries=3 --timeout=300
```

Tail Laravel logs in PowerShell:

```powershell
cd C:\LCRO-System\final_capstone
Get-Content storage\logs\laravel.log -Wait -Tail 100
```

## 17. Create `START_LCRO_SYSTEM.bat`

Create this file after manual testing works:

```text
C:\LCRO-System\START_LCRO_SYSTEM.bat
```

Content:

```bat
@echo off
title LCRO System Launcher

set BASE=C:\LCRO-System
set APP=C:\LCRO-System\final_capstone
set OCR=C:\LCRO-System\final_capstone\paddle_ocr_service
set BRIDGE=C:\LCRO-System\scanner-bridge

echo Starting LCRO local services...
echo.
echo Make sure XAMPP Apache/MySQL and Ganache GUI are already open.
echo.

start "LCRO Laravel Server" cmd /k "cd /d %APP% && php artisan serve --host=127.0.0.1 --port=8000"
timeout /t 2 /nobreak >nul

start "LCRO Paddle OCR Service" cmd /k "cd /d %OCR% && call venv\Scripts\activate.bat && uvicorn main:app --host 127.0.0.1 --port 8001"
timeout /t 2 /nobreak >nul

start "LCRO Blockchain Queue Worker" cmd /k "cd /d %APP% && php artisan queue:work --verbose --tries=3 --timeout=300"
timeout /t 2 /nobreak >nul

start "LCRO Scanner Bridge" cmd /k "cd /d %BRIDGE% && node server.js"
timeout /t 8 /nobreak >nul

start "" http://127.0.0.1:8000

echo Done. Keep all command windows open.
pause
```

Before using the BAT each day:

1. Open XAMPP and start Apache/MySQL.
2. Open Ganache GUI.
3. Double-click `START_LCRO_SYSTEM.bat`.

## 18. What Windows Must Stay Open

Keep these open while using the system:

- XAMPP Control Panel
- Ganache GUI
- Laravel command window
- Paddle OCR command window
- Blockchain queue worker command window
- Scanner bridge command window

Instruction for LGU staff:

```text
Do not close the black command windows. Just minimize them. If a black command window is closed, that service stops.
```

The browser window can be closed and reopened at:

```text
http://127.0.0.1:8000
```

## 19. Deployment-Day Checklist

Before leaving home:

- Fresh SQL backup created.
- Project folder copied.
- Scanner bridge folder copied.
- Uploaded document files copied.
- `.env` backup copied.
- Installers copied.
- Admin/staff/supervisor test accounts written down.
- USB tested on another folder.
- Scanner driver installer confirmed.
- Optional Python wheels or tested OCR `venv` copied.

After arriving at LGU:

- Create `C:\LCRO-System`.
- Copy project folders.
- Disable sleep while plugged in.
- Connect scanner by USB.
- Install scanner driver.

After installing dependencies:

- `php -v` works.
- `composer --version` works.
- `node -v` works.
- `npm -v` works.
- `python --version` works.
- XAMPP Apache/MySQL starts.
- Ganache opens.
- Scanner works in Windows.

After importing database:

- `final_capstone` database exists.
- Tables are visible in phpMyAdmin.
- Login accounts exist.
- Uploaded document files are present.

After configuring `.env`:

- `APP_URL=http://127.0.0.1:8000`
- DB values are correct.
- `GANACHE_URL=http://127.0.0.1:7545`
- `GANACHE_FROM_ADDRESS` matches Ganache first account.
- `SCANNER_BRIDGE_URL=http://127.0.0.1:3000`
- `PADDLE_OCR_URL=http://127.0.0.1:8001`
- `php artisan optimize:clear` was run.

After setting up Ganache:

- RPC test returns block number.
- `eth_accounts` returns accounts.
- Laravel Tinker can instantiate `GanacheBlockchainService`.

After scanner test:

- `http://127.0.0.1:3000/health` works.
- `http://127.0.0.1:3000/scanners` detects scanner.
- Test scan creates file.

After OCR test:

- `http://127.0.0.1:8001/health` works.
- OCR extracts text from test image.

After blockchain anchoring test:

- Queue worker processes job.
- Ganache shows new transaction.
- Latest scan has `blockchain_status=confirmed`.
- Latest scan has `blockchain_tx_hash`.

After full workflow test:

- search works.
- preview works.
- print works.
- release workflow works.
- correction/retry workflow works if part of demo.
- backup plan exists.

## 20. Troubleshooting

`php` not recognized:

```bat
C:\xampp\php\php.exe -v
```

Add `C:\xampp\php` to PATH, then open a new Command Prompt.

`composer` not recognized:

- Reinstall Composer.
- Select `C:\xampp\php\php.exe`.
- Or use:

```bat
cd /d C:\LCRO-System\final_capstone
php composer.phar install
```

`node` not recognized:

- Reinstall Node.js LTS.
- Make sure "Add to PATH" is enabled.
- Open a new Command Prompt.

`npm install` error:

```bat
cd /d C:\LCRO-System\final_capstone
npm cache verify
npm install
npm run build
```

If internet is unavailable, use the copied `node_modules` fallback or install earlier while internet is available.

Port `8000` already in use:

```bat
netstat -ano | findstr :8000
taskkill /PID PID_NUMBER /F
```

Then restart:

```bat
php artisan serve --host=127.0.0.1 --port=8000
```

Port `8001` already in use:

```bat
netstat -ano | findstr :8001
taskkill /PID PID_NUMBER /F
```

Then restart Paddle OCR.

Port `3000` already in use:

```bat
netstat -ano | findstr :3000
taskkill /PID PID_NUMBER /F
```

Keep scanner bridge on `3000` because the current frontend directly calls `http://127.0.0.1:3000`.

Database connection error:

- Start MySQL in XAMPP.
- Check `.env` DB values.
- Confirm `final_capstone` database exists.
- Run:

```bat
php artisan config:clear
php artisan optimize:clear
```

Storage image not showing:

```bat
cd /d C:\LCRO-System\final_capstone
php artisan storage:link
```

Also confirm uploaded files were copied into:

```text
C:\LCRO-System\final_capstone\storage\app\public
```

OCR not responding:

- Confirm Paddle OCR command window is open.
- Test `http://127.0.0.1:8001/health`.
- Increase timeout:

```dotenv
PADDLE_OCR_TIMEOUT=120
```

- Run:

```bat
php artisan config:clear
```

Scanner bridge not detecting scanner:

- Test scanner in Windows first.
- Reinstall scanner driver.
- Try another USB port.
- Run scanner bridge as Administrator.
- Test `http://127.0.0.1:3000/scanners`.
- If PowerShell scripts are blocked:

```powershell
Set-ExecutionPolicy -Scope CurrentUser RemoteSigned
```

Queue worker not processing:

```bat
cd /d C:\LCRO-System\final_capstone
php artisan queue:work --verbose --tries=3 --timeout=300
php artisan queue:failed
```

If `.env` changed:

```bat
php artisan queue:restart
php artisan config:clear
```

Then restart the queue worker window.

Ganache transaction failed:

- Confirm Ganache is open.
- Confirm RPC URL is `http://127.0.0.1:7545`.
- Confirm `GANACHE_FROM_ADDRESS` exactly matches an account shown in Ganache.
- Confirm the account has test ETH.
- Run RPC block test.
- Clear config and restart queue worker.

Contract address invalid:

- Current document anchoring does not require `BLOCKCHAIN_CONTRACT_ADDRESS`.
- If using a real contract, deploy it to the same fresh Ganache workspace and paste the new address.
- Contract address must look like `0x` plus 40 hex characters.

Private key invalid:

- Copy it from the same Ganache account used in `GANACHE_FROM_ADDRESS`.
- Do not include extra spaces.
- Current document anchoring does not use the private key directly, but keep it correct for future signed-transaction code.

Document not anchoring:

- Queue worker must be open.
- Ganache must be open.
- Document must be ready for anchoring.
- In current code, `Scan::isReadyForBlockchainAnchoring()` requires completed verification, not already confirmed/failed, and enough validation/manual completion score.
- Check logs:

```powershell
cd C:\LCRO-System\final_capstone
Get-Content storage\logs\laravel.log -Wait -Tail 100
```

## 21. Simple Explanation For Adviser

The system is deployed locally on one LGU computer. XAMPP runs Apache/MySQL, Ganache runs the local blockchain, Paddle OCR handles OCR, scanner bridge handles the USB scanner, and the Laravel queue worker processes blockchain anchoring. The staff only opens the local browser system and does not need cloud access.
