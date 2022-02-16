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
    protected ?string $url;
    protected ?string $host;
    protected ?int $total;
    protected array $folders;
    protected array $rejects;

    public function __construct(string $user, string $password, ?string $url = null, ?string $host = null) {
        $this->user = $user;
        $this->password = $password;
        $this->url = $url;
        $this->host = $host;
        $this->connection = null;
        $this->total = null;
        $this->folders = array();
        $this->rejects = array();
    }

    private function get(string $property) { if(property_exists($this, $property)) return $this->$property; }
    
    private function set(string $property, $value) {
        if(property_exists($this, $property)) $this->$property = $value;
        return $this;
    }
    
    public function getConnection() { return $this->conenction; }
    public function setConnection() { $this->close()->open(); }
    
    public function getTotal(bool $check = true) {
        if($check || $this->total == null):
            if($this->open(false)):
                $total = imap_num_msg($this->connection);
                if($total !== false) $this->total = $total;
            endif;
        endif;
        return $this->total;
    }
    
    public function __call(string $name, array $arguments) {
        $length = strlen($name);
        for($i = 0; $i < $length; $i++):
            if($name{$i} === strtoupper($name{$i})):
                $method = substr($name, 0, $i);
                $property = lcfirst(substr($name, $i, $length));
                switch($method):
                    case 'get': return $this->$method($property); break;
                    case 'set': $this->$method($property, $arguments[0]); return $this; break;
                endswitch;
            endif;
        endfor;
    }

    public function open(bool $flag = true) { 
        if(!empty($this->url)):
            if(empty($this->connection)):
                $this->connection = imap_open($this->url, $this->user, $this->password);
            endif;
            if($flag) return $this;
            else return $this->connection != false;
        else:
            return false;
        endif;
    }
    
    public function close() { 
        if(!empty($this->connection)):
            imap_close($this->connection); 
            $this->connection = null;
        endif;
        return $this;
    }
    
    public function read(string $from, string $to, bool $details = false, bool $attachments = false) {
        $emails = array();
        if($this->open(false)):
            $total = $this->getTotal(true);
            if(!empty($total)):
                $to = $total - $to;
                $from = $total - $from;
                if($from < 1) $from = 1;
                //var_dump($total,$to,$from, "{$from}:{$to}"); die();
                $rows = imap_fetch_overview($this->connection,"{$from}:{$to}",0);
                foreach($rows as $row):
                    $messages = array();
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
    
    private function getEmail(int $uid, array $parts, bool $details = false, bool $attachments = false, stdClass $parent = null, string $prefix = '', int $index = 1, bool $fullPrefix = true) {
        $email = new stdClass();
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
                        if(!in_array($part->filename, $this->rejects)):
                            $tmp = new stdClass();
                            $tmp->number = "{$number}";
                            $tmp->type = $part->type;
                            $tmp->subtype = $part->subtype;
                            $tmp->encoding = $part->encoding;
                            $tmp->filename = $part->filename;
                            if($attachments) $tmp->text = $this->getAttachment($uid, "{$number}", $part->encoding);
                            $email->attachments[] = $tmp;
                        endif;
                    elseif($part->subtype == "PLAIN"):
                            $tmp = new stdClass();
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
                        if(!in_array($part->filename, $this->rejects)):
                            $tmp = new stdClass();
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

    public function getFolders() {
        if($this->open(false)) $this->folders = imap_list($this->connection, "{$this->url}", "*");
        return $this->folders;
    }
    
    public function toFolder(string $folder, string $message) {
        $folder = "{$this->url}{$folder}";
        if($this->open(false) && in_array($folder, $this->getFolders())):
            $result = imap_append($this->connection, $folder, $message);
        endif;
        return $result;
    }

    public function getAttachment(int $uid, string $number, int $encoding, bool $headers = false, string $filename) {
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
        endif;
        return null;
    }

}
?>
