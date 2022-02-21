<?php

/**
 * This class provides a wrapper for commonly used PHP IMAP functions.
 *
 * This class is written by Loris Salvi.
 *
 * @author Loris Salvi (loris[at]duck[dot]com)
 */

namespace Taniguchi;

class Imap {
    protected $connection;
    protected string $user;
    protected string $password;
    protected string $url;
    protected ?int $port;
    protected ?array $flags;
    protected ?int $total;
    protected array $folders;
    protected array $rejects;

    /**
    * Inizialize the Imap class.
    *
    * @param string     $user       Email for the Imap's login
    * @param string     $password   Password for the Imap's login
    * @param string     $url        Url for the Imap's login
    * @param int        $port       (optional) Port for the Imap's login. Default value is null
    * @param string     ...$flags   (optional) Flags for the Imap's login
    *
    * @return \Taniguchi\Imap       Returns class object
    */
    public function __construct(string $user, string $password, string $url, int $port = null, string ...$flags) {
        $this->connection = null;
        $this->user = $user;
        $this->password = $password;
        $this->url = $url;
        $this->port = $port;
        if(!empty($this->port)):
            $this->flags = array('service=imap' => true); 
            if(!empty($flags)) $this->flags = array_merge($this->flags, $flags);
        endif;
        $this->total = 0;
        $this->folders = array();
        $this->rejects = array();
    }

    private function get(string $property) { 
        if(property_exists($this, $property) && isset($this->$property)) return $this->$property; 
        else return null;
    }
    
    private function set(string $property, $value) { if(property_exists($this, $property)) $this->$property = $value; }
    
    /**
    * If exists close the actual connection and open a new one.
    *
    * @return \Taniguchi\Imap       Returns class object
    */
    public function setConnection() { return $this->close()->open(); }
    
    /**
    * Add files to reject when downloading attachments.
    *
    * @param string     ...$items   Name of files to reject
    *
    * @return \Taniguchi\Imap       Returns class object
    */
    public function addRejects(string ...$items) { 
        foreach($items as $item) $this->rejects[$item] = true; 
        return $this;
    }
    
    /**
    * Remove files from reject list when downloading attachments.
    *
    * @param string     ...$items   Name of files to remove
    *
    * @return \Taniguchi\Imap       Returns class object
    */
    public function delRejects(string ...$items) { 
        foreach($items as $item):
            if(isset($this->rejects[$item])) unset($this->rejects[$item]); 
        endforeach;
        return $this;
    }
    
    /**
    * Check if a file name is in the reject's list.
    *
    * @param string     $name       Name of file to check
    *
    * @return boolean               Returns true if exist or false if not
    */
    public function getReject(string $name) { return isset($this->rejects[$name]); }
    
    /**
    * Set flags for Imap connection.
    *
    * @param string     $name       Name of file to check
    * @param bool       $value      (optional) Set to true to enable or false to disable. Default value is 'true'
    *
    * @return \Taniguchi\Imap       Returns class object
    */
    public function setFlags(string $name, bool $value = true) { 
        switch($name):
            case 'anonymous': 
                if($value) $this->flags['anonymous'] = true; 
                elseif(isset($this->flags['anonymous'])) unset($this->flags['anonymous']);
                break;
            case 'debug': 
                if($value) $this->flags['debug'] = true; 
                elseif(isset($this->flags['debug'])) unset($this->flags['debug']);
            case 'secure': 
                if($value) $this->flags['secure'] = true; 
                elseif(isset($this->flags['secure'])) unset($this->flags['secure']);
            case 'nntp': 
                if($value) $this->flags['nntp'] = true; 
                elseif(isset($this->flags['nntp'])) unset($this->flags['nntp']);
            case 'norsh': 
                if($value) $this->flags['norsh'] = true; 
                elseif(isset($this->flags['norsh'])) unset($this->flags['norsh']);
            case 'ssl': 
                if($value) $this->flags['ssl'] = true; 
                elseif(isset($this->flags['ssl'])) unset($this->flags['ssl']);
            case 'validate': 
                if($value):
                    if(isset($this->flags['novalidate-cert'])) unset($this->flags['novalidate-cert']);
                    $this->flags['validate-cert'] = true;
                else:
                    if(isset($this->flags['validate-cert'])) unset($this->flags['validate-cert']);
                    $this->flags['novalidate-cert'] = true;
                endif;
                break;
            case 'tls': 
                if($value):
                    if(isset($this->flags['notls'])) unset($this->flags['notls']);
                    $this->flags['tls'] = true;
                else:
                    if(isset($this->flags['tls'])) unset($this->flags['tls']);
                    $this->flags['notls'] = true;
                endif;
                break;
            case 'readonly': 
                if($value) $this->flags['readonly'] = true; 
                elseif(isset($this->flags['readonly'])) unset($this->flags['readonly']);
        endswitch;
        return $this;
    }
    
    /**
    * Set the flag "anonymous" of Imap connection.
    *
    * @param bool       $value      (optional) Set to true to enable or false to disable. Default value is 'true'
    *
    * @return \Taniguchi\Imap       Returns class object
    */
    public function setAnonymous(bool $value = true) { return $this->setFlags('anonymous', $value); }
        
    /**
    * Set the flag "debug" of Imap connection.
    *
    * @param bool       $value      (optional) Set to true to enable or false to disable. Default value is 'true'
    *
    * @return \Taniguchi\Imap       Returns class object
    */
    public function setDebug(bool $value = true) { return $this->setFlags('debug', $value); }
            
    /**
    * Set the flag "secure" of Imap connection.
    *
    * @param bool       $value      (optional) Set to true to enable or false to disable. Default value is 'true'
    *
    * @return \Taniguchi\Imap       Returns class object
    */
    public function setSecure(bool $value = true) { return $this->setFlags('secure', $value); }
            
    /**
    * Set the flag "nntp" of Imap connection.
    *
    * @param bool       $value      (optional) Set to true to enable or false to disable. Default value is 'true'
    *
    * @return \Taniguchi\Imap       Returns class object
    */
    public function setNntp(bool $value = true) { return $this->setFlags('nntp', $value); }
            
    /**
    * Set the flag "norsh" of Imap connection.
    *
    * @param bool       $value      (optional) Set to true to enable or false to disable. Default value is 'true'
    *
    * @return \Taniguchi\Imap       Returns class object
    */
    public function setNorsh(bool $value = true) { return $this->setFlags('norsh', $value); }
            
    /**
    * Set the flag "ssl" of Imap connection.
    *
    * @param bool       $value      (optional) Set to true to enable or false to disable. Default value is 'true'
    *
    * @return \Taniguchi\Imap       Returns class object
    */
    public function setSsl(bool $value = true) { return $this->setFlags('ssl', $value); }
            
    /**
    * Set the flag "tls" of Imap connection.
    *
    * @param bool       $value      (optional) Set to true to enable or false to disable. Default value is 'true'
    *
    * @return \Taniguchi\Imap       Returns class object
    */
    public function setTls(bool $value = true) { return $this->setFlags('tls', $value); }
            
    /**
    * Set the flag "readonly" of Imap connection.
    *
    * @param bool       $value      (optional) Set to true to enable or false to disable. Default value is 'true'
    *
    * @return \Taniguchi\Imap       Returns class object
    */
    public function setReadonly(bool $value = true) { return $this->setFlags('readonly', $value); }
            
    /**
    * Get the mailbox of the Imap connection.
    *
    * @return string                Returns class object
    */
    public function getMailbox() { 
        if(empty($this->port)):
            return $this->url;
        else:
            return "{{$this->url}:{$this->port}/" . implode('/', array_keys($this->flags)) . '}'; 
        endif;
    }
                
    /**
    * Get the total number of mails in the Imap's account.
    *
    * @param bool       $check      (optional) Set to true to load the value via Imap. Default value is 'true'
    *
    * @return int                   Returns class object
    */
    public function getTotal(bool $check = true) {
        if($check || empty($this->total)):
            if($this->open(false)):
                $total = imap_num_msg($this->connection);
                if($total !== false) $this->total = $total;
            endif;
        endif;
        return $this->total;
    }
    
    /**
    * Used to call dynamic getter and setter.
    *
    * @param string     $name       Name of th method called
    * @param array      $arguments  Parameters passed to the call
    *
    * @return mixed     With a set, it returns the \Taniguchi\Imap object. With a get, it returns the method's type
    */
    public function __call(string $name, array $arguments) {
        $length = strlen($name);
        for($i = 0; $i < $length; $i++):
            if($name{$i} === strtoupper($name{$i})):
                $method = substr($name, 0, $i);
                $property = lcfirst(substr($name, $i, $length));
                switch($method):
                    case 'get': return $this->$method($property); break;
                    case 'set': $this->$method($property, $arguments[0]); break;
                endswitch;
            endif;
        endfor;
        return $this;
    }

    /**
    * Call this function to open the connection of the Imap.
    *
    * @param bool       $flag       (optional) Set to true to return \Taniguchi\Imap object. Default value is 'true'
    *
    * @return mixed     Based on the flag, it returns \Taniguchi\Imap object or the status of the connection (boolean)
    */
    public function open(bool $flag = true) { 
        if(!empty($this->getMailbox())):
            if(empty($this->connection)):
                $this->connection = @imap_open($this->getMailbox(), $this->user, $this->password);
                if(!$this->connection):
                    imap_errors();
                    imap_alerts();
                endif;
            endif;
            if($flag) return $this;
            else return $this->connection != false;
        else:
            return false;
        endif;
    }
    
    /**
    * Call this function to close the connection of the Imap.
    *
    * @return \Taniguchi\Imap       Returns class object
    */
    public function close() { 
        if(!empty($this->connection)):
            imap_close($this->connection); 
            $this->connection = null;
        endif;
        return $this;
    }
    
    /**
    * Gets all the emails from Imap. It doesn't look from a specific folder, but among all existing emails.
    * It starts from the last email received to the first.
    *
    * @param int        $from           Index of the email whence to start
    * @param int        $to             Number of emails to get
    * @param bool       $details        (optional) If true it will get the text of the main message and all the .eml. Default value is 'false'
    * @param bool       $attachments    (optional) If true it will get all the attachments (.eml attachments too). Default value is 'false'
    *
    * @return array     Returns an array of \stdClass with all the informations about the emails
    */
    public function read(int $from, int $to, bool $details = false, bool $attachments = false) {
        $emails = array();
        if($this->open(false)):
            $total = $this->getTotal(true);
            if(!empty($total)):
                if($from < 0) $from = 0;
                if($to < 1) $to = 1;
                else if($to >= $total) $to = $total-1;
                $to = $total - $to;
                $from = $total - $from;
                $rows = imap_fetch_overview($this->connection,"{$from}:{$to}",0);
                foreach($rows as $row):
                    $messageNumber = $row->uid;
                    $structure = imap_fetchstructure($this->connection, $messageNumber, FT_UID);
                    if($details): 
                        $item = $this->getEmail($messageNumber, $structure->parts, $details, $attachments);
                    else:
                        $item = new \stdClass();
                    endif;
                    $item->uid = $messageNumber;
                    $item->from = explode(' ', str_replace(['"', 'Per conto di: '], '', $row->from))[0];
                    $item->subject = $row->subject;
                    $item->date = new \DateTime(null, new \DateTimeZone('Europe/Rome'));
                    $item->date->setTimestamp($row->udate);
                    $emails[] = $item;
                endforeach;
            endif;
        endif;
        return $emails;
    }
    
    private function getEmail(int $uid, array $parts, bool $details = false, bool $attachments = false, string $parent = null, string $prefix = '', int $index = 1, bool $fullPrefix = true) {
        $email = new \stdClass();
        $email->messages = array();
        $email->attachments = array();
        $toRejects = array('smime.p7s', 'daticert.xml');
        foreach($parts as $part):
            $filename = '';
            if($part->ifdparameters):
                foreach($part->dparameters as $object):
                    if(strtolower($object->attribute) == 'filename'):
                        $filename = $object->value;
                    endif;
                endforeach;
            endif;
            if(!$filename && $part->ifparameters):
                foreach($part->parameters as $object):
                    if(strtolower($object->attribute) == 'name'):
                        $filename = $object->value;
                    endif;
                endforeach;
            endif;
            $part->filename = $filename;
            $number = "{$prefix}{$index}";
            switch($part->type):
                case 0: 
                    if(isset($part->filename) && !empty($part->filename)):
                        if(!$this->getReject($part->filename)):
                            $tmp = new \stdClass();
                            $tmp->number = "{$number}";
                            $tmp->type = $part->type;
                            $tmp->subtype = $part->subtype;
                            $tmp->encoding = $part->encoding;
                            $tmp->filename = $part->filename;
                            if($attachments) $tmp->text = $this->getAttachment($uid, "{$number}", $part->encoding);
                            $email->attachments[] = $tmp;
                        endif;
                    elseif($part->subtype == "PLAIN"):
                            $tmp = new \stdClass();
                            $tmp->number = "{$number}";
                            $tmp->type = $part->type;
                            $tmp->subtype = $part->subtype;
                            $tmp->encoding = $part->encoding;
                            $tmp->filename = !empty($parent) ? $parent : $part->filename;
                            if($details) $tmp->text = $this->getAttachment($uid, "{$number}", $part->encoding);
                            $email->messages[] = $tmp;
                    endif;
                    break;
                case 1: case 2:  break;
                case 3: case 4: case 5: case 6: case 7: // application, audio, image, video, other
                    if(isset($part->filename) && !empty($part->filename)):
                        if(!$this->getReject($part->filename)):
                            $tmp = new \stdClass();
                            $tmp->number = "{$number}";
                            $tmp->type = $part->type;
                            $tmp->subtype = $part->subtype;
                            $tmp->encoding = $part->encoding;
                            $tmp->filename = $part->filename;
                            if($attachments) $tmp->text = $this->getAttachment($uid, "{$number}", $part->encoding);
                            $email->attachments[] = $tmp;
                        endif;
                    endif;
                    break;
            endswitch;
            if(isset($part->parts)):
                $tmp = (!empty($part->filename)) ? $part->filename : (!empty($parent) ? $parent : '');
                if($part->type == 2):
                    $item = $this->getEmail($uid, $part->parts, $details, $attachments, $tmp, "{$prefix}{$index}.", 0, false);
                elseif($fullPrefix):
                    $item = $this->getEmail($uid, $part->parts, $details, $attachments, $tmp, "{$prefix}{$index}.");
                else:
                    $item = $this->getEmail($uid, $part->parts, $details, $attachments, $tmp, $prefix);
                endif;
                $email->messages = array_merge($email->messages, $item->messages);
                $email->attachments = array_merge($email->attachments, $item->attachments);
            endif;
            $index++;
        endforeach;
        return $email;
    }
    
    /**
    * Get the an attachment of the email. Used usually when  flags "details" or "attachments" are set to false.
    *
    * @param int        $uid            UID of the Imap email
    * @param int        $number         Number's part of the Imap email
    * @param int        $encoding       Encode of the Imap email
    * @param bool       $headers        (optional) If true it will edit headers to return the attachemnt in the request. Default value is 'false'
    * @param string     $filename       (optional) If it has an empty value, the alue will be the tiemstamp withouth an extension. Default value is '' (empty)
    *
    * @return mixed     Returns the value of the attachment or it will edit the headers of the request
    */
    public function getAttachment(int $uid, string $number, int $encoding, bool $headers = false, string $filename = '') {
        if($this->open(false)):
            $data = imap_fetchbody($this->connection, $uid, "{$number}", FT_UID);
            switch($encoding):
                case 0: break; // 7BIT
                case 1: break; // 8BIT
                case 2: break; // BINARY
                case 3: $data = base64_decode($data); break; // BASE64
                case 4: $data = quoted_printable_decode($data); break; // QUOTED_PRINTABLE
                case 5: break; // OTHER
            endswitch;
            if($headers): 
                if(empty($filename)) $filename = (new DateTime())->getTimestamp();
                header("Content-Description: File Transfer");
                header("Content-Type: application/octet-stream");
                header("Content-Type: application/force-download");
                header("Content-Type: application/download");
                header("Content-Disposition: attachment; filename={$filename}");
                header("Content-Transfer-Encoding: binary");
                header("Expires: 0");
                header("Cache-Control: must-revalidate");
                header("Pragma: public");
                echo $data;
                return;
            endif;
            return $data;
        endif;
        return null;
    }
    
    /**
    * Get all the Imap folders.
    *
    * @return array     Returns an array of name's folder
    */
    public function getFolders() {
        if($this->open(false)) $this->folders = imap_list($this->connection, $this->getMailbox(), "*");
        return $this->folders;
    }
    
    /**
    * Save an email in a specific Imap folder. If the folder doesn't exist, the email will be lost.
    *
    * @param string     $folder     
    * @param string     $message    
    *
    * @return bool      Returns true if the message saving was successful, false otherwise
    */
    public function toFolder(string $folder, string $message) {
        $folder = $this->getMailbox() . $folder;
        if($this->open(false) && in_array($folder, $this->getFolders())):
            $result = imap_append($this->connection, $folder, $message);
        endif;
        return $result;
    }
}
?>
