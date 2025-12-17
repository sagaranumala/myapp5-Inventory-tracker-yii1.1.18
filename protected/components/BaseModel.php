<?php
// protected/components/BaseModel.php

class BaseModel extends CActiveRecord
{
    protected function generateUlid()
    {
        $chars = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
        $time = (int)(microtime(true) * 1000);
        $timeChars = '';
        for ($i = 0; $i < 10; $i++) {
            $timeChars = $chars[$time % 32] . $timeChars;
            $time = intdiv($time, 32);
        }
        $randomChars = '';
        for ($i = 0; $i < 16; $i++) {
            $randomChars .= $chars[random_int(0, 31)];
        }
        return $timeChars . $randomChars;
    }

    protected function beforeValidate()
    {
        if ($this->isNewRecord) {
            foreach ($this->ulidFields() as $field) {
                if (empty($this->$field)) {
                    $this->$field = $this->generateUlid();
                }
            }
        }
        return parent::beforeValidate();
    }

    protected function ulidFields()
    {
        return [];
    }
}
