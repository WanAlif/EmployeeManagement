<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "employee".
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $position
 * @property float|null $salary
 * @property string $created_at
 * @property string $updated_at
 */
class Employee extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'employee';
    }

    /**
     * Add timestamp behavior for created_at and updated_at
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     * VALIDATION RULES
     */
    public function rules()
    {
        return [
            // Required fields
            [['name', 'email', 'position'], 'required'],
            
            // Email validation
            ['email', 'email', 'message' => 'Please enter a valid email address'],
            ['email', 'unique', 'message' => 'This email is already registered'],
            
            // String length validation
            [['name', 'position'], 'string', 'max' => 100],
            ['email', 'string', 'max' => 100],
            
            // Number validation
            ['salary', 'number', 'min' => 0],
            ['salary', 'default', 'value' => null],
            
            // Safe attributes
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     * ATTRIBUTE LABELS
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Full Name',
            'email' => 'Email Address',
            'position' => 'Position',
            'salary' => 'Salary ($)',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}