<?php
class AuditLog extends BaseModel
{
    public function tableName() { return 'auditLogs'; }
    protected function ulidFields() { return ['auditId']; }

    public function rules()
    {
        return [
            ['action, tableName, recordId, userId', 'required'],
            ['action, tableName, recordId, oldData, newData', 'length', 'max'=>255],
        ];
    }

    public function relations()
    {
        return [
            'user' => [self::BELONGS_TO, 'User', 'userId'],
        ];
    }
}
