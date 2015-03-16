<?php

//At next use make this uppercase!!!
final class upload {

    private $path = null;
    private $exts = null;
    private $md5 = false;
    private $multi = false;
    private $formName = 'file';
    private $maxSize = null;
    private $canBeEmpty = true;
    private $defaultName;
    private $ctr = 0;

    public function __construct($path, $shouldCreate = false, $createRecursively = false, $chmod = 0775) {

        if(!$shouldCreate && !is_dir($path))
            throw new Exception('Not a valid path was given.');
        else if ($shouldCreate && !is_dir($path)) {
            if(!mkdir($path, $chmod, $createRecursively))
                return new Exception('Could not create directory.');
        }
        if (preg_match("/.*(\\|\/){1}$/", $path))
            $path = substr($path, 0, -1);

        $this->path = $path;

        $this->defaultName = (string)bin2hex(mcrypt_create_iv(22, MCRYPT_DEV_URANDOM));
    }

    // Builders from now on

    public function exts($exts) {
        if (is_array($exts))
            foreach($exts as $i => $ext)
                if(count($bumm = explode('/', $ext)) > 1)
                    $exts[$i] = $bumm[1];
        $this->exts = $exts;
        return $this;
    }

    public function md5($md5) {
        if (!is_bool($md5))
            throw new Exception('Not boolean given.');
        $this->md5 = $md5;
        return $this;
    }

    public function multi($multi) {
        if (!is_bool($multi))
            throw new Exception('Not boolean given.');
        $this->multi = $multi;
        return $this;
    }

    public function formName($formName) {
        if (!is_string($formName))
            throw new Exception('Not string given.');
        $this->formName = $formName;
        return $this;
    }

    public function maxSize($maxSize, $metric = '') {
        if (!is_numeric($maxSize))
            throw new Exception('Not numeric type given.');
        $multiper;
        switch(strtolower($metric)) {
            case '':
                $multiper = 1;
                break;
             case 'kb':
                $multiper = 1024;
                break;
            case 'mb':
                $multiper = 1048576;
                break;
            case 'gb':
                $multiper = 1073741824;
                break;
            default:
                throw new Exception('Invalid metric given.');
        }
        $this->maxSize = (int)($maxSize * $multiper);
        return $this;
    }

    public function canBeEmpty($canBeEmpty) {
        if (!is_bool($canBeEmpty))
            throw new Exception('Not boolean given.');
        $this->canBeEmpty = $canBeEmpty;
        return $this;
    }

    public function setToPics() {
        $this->exts = array('jpeg', 'jpg', 'pjpeg', 'png', 'x-png');
        return $this;
    }

    public function defaultName($defaultName) {
        $this->defaultName = $defaultName;
        return $this;
    }

    // End of builder functions

    public function exec() {
        if(!$this->canBeEmpty && count($_FILES) == 0)
            throw new Exception('Nothing is uploaded.');
        if(!isset($_FILES[$this->formName]))
            throw new Exception('Wrong form name.');

        if ($this->multi) {
            if (!$this->canBeEmpty && empty($_FILES[$this->formName]['name'][0]))
                throw new Exception('Nothing is uploaded.');
            if(!is_array($_FILES[$this->formName]['name']))
                throw new Exception('Form field was single, multiple expected.');
            return $this->multi_up();
        } else {
            if (!$this->canBeEmpty && empty($_FILES[$this->formName]['name']))
                throw new Exception('Nothing is uploaded.');
            if(is_array($_FILES[$this->formName]['name']))
                throw new Exception('Form field was multiple, single expected.');
            return $this->single_up();
        }
    }

    private function multi_up() {
        $arr = array();
        for($i=0; $i < count($_FILES[$this->formName]['name']); $i++) {
            if (empty($_FILES[$this->formName]['name'][$i]))
                continue;
            $str = $this->processFile($_FILES[$this->formName]['name'][$i], $_FILES[$this->formName]['type'][$i], $_FILES[$this->formName]['tmp_name'][$i], $_FILES[$this->formName]['size'][$i]);
            if(!is_null($str))
                $arr[] = $str;
        }
        return $arr;
    }

    private function single_up() {
        if (empty($_FILES[$this->formName]['name']))
            return null;
        return $this->processFile($_FILES[$this->formName]['name'], $_FILES[$this->formName]['type'], $_FILES[$this->formName]['tmp_name'], $_FILES[$this->formName]['size']);
    }

    private function processFile($name, $type, $tmp_name, $size) {
        if(count($bumm = explode('/', $type)) == 2)
            $type = $bumm[1];

        if($this->maxSize != null && $this->maxSize < $size) {
            unlink($tmp_name);
            return null;
        }

        if(!$this->isValidExt($type)) {
            unlink($tmp_name);
            return null;
        }

        $newName = ($this->md5 ? $this->getMd5Name($tmp_name, $type) : $this->getSimpleName($type));

        if (move_uploaded_file($tmp_name, $this->path . '/' . $newName))
            return $newName;

        unlink($tmp_name);
        return null;
    }

    private function getSimpleName($ext) {
        return $this->defaultName . '_' . ($this->ctr++) . $ext;
    }

    private function getMd5Name($file, $ext) {
        return md5_file($file) . ".$ext";
    }

    private function isValidExt($ext) {
        if ($this->exts == null) return true;

        return in_array($ext, $this->exts);
    }

}