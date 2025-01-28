CREATE USER 'testsendgrid1'@'%' IDENTIFIED BY '123qweasdzxc';
GRANT ALL PRIVILEGES ON redirect_db.* TO 'testsendgrid1'@'%';
FLUSH PRIVILEGES;
