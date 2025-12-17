<?php
class Notification extends BaseModel
{
    public function tableName() { return 'notifications'; }
    protected function ulidFields() { return ['notificationId']; }

    public function rules()
    {
        return [
            ['title, message, userId', 'required'],
            ['title', 'length', 'max'=>255],
            ['isRead', 'boolean'],
        ];
    }

    public function relations()
    {
        return [
            'user' => [self::BELONGS_TO, 'User', 'userId'],
        ];
    }
}
