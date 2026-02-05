#!/bin/bash
# Masukkan data FTP cPanel kamu di bawah
HOST="ftp.warkop69ajaib.xo.je"
USER="username_kamu"
PASS="password_kamu"

lftp -f "
open $HOST
user $USER $PASS
# mirror -R akan mengupload semua folder Website di Termux ke cPanel
mirror -R ~/Website /public_html
bye
"
echo "🚀 Selesai! Script cPanel sudah terupdate dari Termux."
