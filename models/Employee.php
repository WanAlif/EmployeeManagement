<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * Employee Model
 * 
 * This is the model class for table "employee".
 * Handles employee data management with validation and business logic.
 *
 * @property int $id Employee ID (Primary Key)
 * @property string $name Full name of the employee
 * @property string $email Email address (unique)
 * @property string $position Job position/title
 * @property float|null $salary Monthly salary
 * @property string $created_at Record creation timestamp
 * @property string $updated_at Last update timestamp
 * 
 * @author Your Name
 * @since 1.0
 */
class Employee extends ActiveRecord
{
    // Constants for better maintainability
    const SCENARIO_CREATE = 'create';
    const SCENARIO_UPDATE = 'update';
    
    // Salary range constants
    const MIN_SALARY = 0;
    const MAX_SALARY = 999999.99;
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'employee';
    }

    /**
     * Attach behaviors to the model
     * 
     * @return array
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
     * Validation rules for employee attributes
     * 
     * Implements comprehensive validation including:
     * - Required field validation
     * - Email format and uniqueness validation
     * - Numeric range validation for salary
     * - String length constraints
     * 
     * @return array
     */
    public function rules()
    {
        return [
            // Required fields validation
            [['name', 'email', 'position'], 'required', 'message' => '{attribute} cannot be blank.'],
            
            // Email validation
            ['email', 'email', 'message' => 'Please enter a valid email address.'],
            [
                'email', 
                'unique', 
                'targetClass' => self::class,
                'message' => 'This email address is already registered.',
                'filter' => function ($query) {
                    // Exclude current record when updating
                    if (!$this->isNewRecord) {
                        $query->andWhere(['!=', 'id', $this->id]);
                    }
                }
            ],
            
            // String length validation
            [['name', 'position', 'email'], 'string', 'max' => 100],
            ['name', 'string', 'min' => 2, 'message' => 'Name must be at least 2 characters.'],
            
            // Trim whitespace
            [['name', 'email', 'position'], 'trim'],
            
            // Salary validation
            ['salary', 'number', 'min' => self::MIN_SALARY, 'max' => self::MAX_SALARY],
            ['salary', 'default', 'value' => null],
            
            // Safe attributes (not directly modifiable)
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    /**
     * Define scenarios for different contexts
     * 
     * @return array
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        
        // All fields available in both scenarios
        $scenarios[self::SCENARIO_CREATE] = ['name', 'email', 'position', 'salary'];
        $scenarios[self::SCENARIO_UPDATE] = ['name', 'email', 'position', 'salary'];
        
        return $scenarios;
    }

    /**
     * Custom attribute labels for form display
     * 
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Employee ID',
            'name' => 'Full Name',
            'email' => 'Email Address',
            'position' => 'Job Position',
            'salary' => 'Monthly Salary',
            'created_at' => 'Created At',
            'updated_at' => 'Last Updated',
        ];
    }

    /**
     * Get formatted salary with currency symbol
     * 
     * @return string
     */
    public function getFormattedSalary()
    {
        return $this->salary !== null 
            ? '$' . number_format($this->salary, 2) 
            : 'Not specified';
    }

    /**
     * Get employee's initials for avatar display
     * 
     * @return string
     */
    public function getInitials()
    {
        $names = explode(' ', trim($this->name));
        $initials = '';
        
        foreach ($names as $name) {
            $initials .= strtoupper(substr($name, 0, 1));
        }
        
        return substr($initials, 0, 2);
    }

    /**
     * Check if employee is high earner (salary > 80000)
     * 
     * @return bool
     */
    public function isHighEarner()
    {
        return $this->salary !== null && $this->salary > 80000;
    }

    /**
     * Before save event handler
     * Perform additional operations before saving
     * 
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        // Convert email to lowercase
        $this->email = strtolower(trim($this->email));
        
        // Capitalize name properly
        $this->name = ucwords(strtolower(trim($this->name)));
        
        return true;
    }

    /**
     * After delete event handler
     * Log deletion or perform cleanup
     * 
     * @return void
     */
    public function afterDelete()
    {
        parent::afterDelete();
        
        // Log the deletion (in production, use proper logging)
        Yii::info("Employee deleted: {$this->name} (ID: {$this->id})", __METHOD__);
    }

    /**
     * Find active employees (example of custom query scope)
     * 
     * @return \yii\db\ActiveQuery
     */
    public static function findActive()
    {
        return self::find(); // Can add conditions like ->where(['status' => 1])
    }

    /**
     * Get employees by position
     * 
     * @param string $position
     * @return array
     */
    public static function getByPosition($position)
    {
        return self::find()
            ->where(['position' => $position])
            ->orderBy(['name' => SORT_ASC])
            ->all();
    }

    /**
     * Get total salary expense
     * 
     * @return float
     */
    public static function getTotalSalaryExpense()
    {
        return (float) self::find()->sum('salary');
    }

    /**
     * Get employee statistics
     * 
     * @return array
     */
    public static function getStatistics()
    {
        return [
            'total_employees' => self::find()->count(),
            'total_salary_expense' => self::getTotalSalaryExpense(),
            'average_salary' => (float) self::find()->average('salary'),
            'highest_salary' => (float) self::find()->max('salary'),
            'lowest_salary' => (float) self::find()->min('salary'),
        ];
    }
}