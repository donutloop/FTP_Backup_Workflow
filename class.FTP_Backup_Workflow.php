<?php

class FTP_Backup_Workflow {

    private $port = 21;            # @var int Port Number
    private $connId = null;        # @var       
    private $ftpServer = null;     # @var string host adress
    private $downloadPath = null;  # @var string Download Folder Path 
    private $tmpPath = null;       # @var string Tmp Folder Path
    private $zipObj = null;        # @var object Zip

    /*
     * @param string $ftpServer
     * @param string $username
     * @param string $password
     * @param int    $port
     * @return void
     */

    public function __construct($ftpServer, $username, $password, $port = null) {
        $this->ftpServer = $ftpServer;
        $this->user = $username;
        $this->password = $password;

        if (is_numeric($port)) {
            $this->port = $port;
        }

        set_time_limit(3600);
        $this->ftpConnect();
        $this->setDownloadPath('/backups/download/');
        $this->setTmpPath($this->downloadPath . 'tmp');
        $this->zipObj = new ZipArchive;
    }

    /*
     * @return void
     */

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

    /*
     * @param string $path
     * @return void
     */

    public function setDownloadPath($path) {
        $this->downloadPath = dirname(dirname(__FILE__)) . $path;
    }

    /*
     * @param string $path
     * @return void
     */

    public function setTmpPath($path) {
        $this->tmpPath = $path;
    }

    /*
     * @param string $path
     * @return boolean
     */

    public function is_FTP_dir($path) {
        if (ftp_size($this->connId, $path) == '-1') {
            $result = true;
        } else {
            $result = false;
        }
        return $result;
    }

    /*
     * @param string $path
     * @return object self
     */

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

    /*
     * @return object self
     */

    public function createZipFile() {
        $today = date("Y-m-d");
        $this->zipObj->open($this->downloadPath . 'backup_' . $today . '.zip', ZipArchive::CREATE);

        return $this;
    }

    /*
     * @param string $path
     * @return object self
     */

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

    /*
     * @return object self
     */

    public function zipClose() {
        if (!$this->zipObj->close()) {
            throw new Exception('Zip close faild');
        }
        return $this;
    }

    /*
     * @param string $path
     * @return object self
     */

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

    /*
     * @return object self
     */

    public function ftpClose() {
        ftp_close($this->connId);
        return $this;
    }
    
    /*
     * @return void
     */

    public function fileExists() {
        $today = date("Y-m-d");
        return file_exists($this->downloadPath . 'backup_' . $today . '.sql');
    }

}
