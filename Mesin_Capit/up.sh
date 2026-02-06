#!/bin/bash

# Masuk ke folder agar tidak salah lokasi
cd ~/Mesin_Capit

# Menangkap semua perubahan yang dibuat oleh bot.php
git add .

# Membuat catatan otomatis (Commit)
git commit -m "Update Otomatis via Bot Telegram"

# Mengirimkan perubahan ke GitHub
git push origin main
