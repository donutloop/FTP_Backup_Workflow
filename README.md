# Using Backup Script 

Example (patternwebsite.php):

### Class require

require_once 'libs/class.FTP_Backup_Workflow.php';

### Databackup

$workflow = new FTP_Backup_Workflow('host','user','password');

$workflow->setDownloadPath( '/backups/download/patternwebsite/' );

$workflow->downloadDir('/patternwebsite')->createZipFile()->zipDir('/patternwebsite')->zipClose()->deleteTmpFiles()->ftpClose();


