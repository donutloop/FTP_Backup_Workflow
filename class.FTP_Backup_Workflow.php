<?php

class FTP_Backup_Workflow {

    private $port = 21;
    private $connId = null;
    private $ftpServer = null;
    private $downloadPath = null;
    private $tmpPath = null;
    private $zipObj = null;

    public function __construct($ftpServer, $username, $password, $port = null) {
        $this->ftpServer = $ftpServer;
        $this->user = $username;
        $this->password = $password;

        if (is_numeric($port)) {
            $this->port = $port;
        }
        
        $this->ftpConnect();

        $this->setDownloadPath('/backups/download/');
        $this->setTmpPath($this->downloadPath . 'tmp');

        $this->zipObj = new ZipArchive;
    }

    private function ftpConnect() {
        $this->connId = ftp_connect('ftp.' . $this->ftpServer, $this->port);

        if ($this->connId == false) {
            throw new Exception('Connection faild');
        }

        $isLogin = ftp_login($this->connId, $this->user, $this->password);

        if ($isLogin == false) {
            throw new Exception('Login faild');
        }

        ftp_pasv($this->connId, true);
    }

    public function setDownloadPath($path) {
        $this->downloadPath = dirname(dirname(__FILE__)) . $path;
    }

    public function setTmpPath($path) {
        $this->tmpPath = $path;
    }

    public function is_FTP_dir($path) {
        if (ftp_size($this->connId, $path) == '-1') {
            $result = true;
        } else {
            $result = false;
        }
        return $result;
    }

    public function downloadDir($path) {

        $exclude = array($path . "/.", $path . "/..");

        mkdir($this->tmpPath . $path, 0777);

        $files = ftp_nlist($this->connId, $path);

        foreach ($files as $aFile) {

            if (in_array($aFile, $exclude)) {
                continue;
            }

            if ($this->is_FTP_dir($aFile)) {
                $this->downloadDir($aFile);
            } else {
                ftp_get($this->connId, $this->tmpPath . $aFile, $aFile, FTP_BINARY);
            }
        }

        return $this;
    }

    public function createZipFile() {
        $today = date("Y-m-d");
        $this->zipObj->open($this->downloadPath . 'backup_' . $today . '.zip', ZipArchive::CREATE);
    }

    public function zipDir($path = null) {
        $tmp = scandir($this->tmpPath . $path);

        $exclude = array(".", "..");

        foreach ($tmp as $aFile) {

            if (in_array($aFile, $exclude)) {
                continue;
            }

            $dir = $this->tmpPath . $path . DIRECTORY_SEPARATOR . $aFile;

            if (is_dir($dir)) {
                $this->zipDir($path . DIRECTORY_SEPARATOR . $aFile);
                $this->zipObj->addEmptyDir($dir);
            } else {
                $this->zipObj->addFile($dir);
            }
        }
        return $this;
    }

    public function zipClose() {
        if (!$this->zipObj->close()) {
            throw new Exception('Zip close faild');
        }
        return $this;
    }

    public function deleteTmpFiles($path = null) {
        $tmp = scandir($this->tmpPath . $path);

        $exclude = array(".", "..");

        foreach ($tmp as $aFile) {

            if (in_array($aFile, $exclude)) {
                continue;
            }

            $dir = $this->tmpPath . $path . DIRECTORY_SEPARATOR . $aFile;
            if (is_dir($dir)) {
                $this->deleteTmpFiles($path . DIRECTORY_SEPARATOR . $aFile);
                rmdir($dir);
            } else {
                unlink($dir);
            }
        }
        return $this;
    }

}
