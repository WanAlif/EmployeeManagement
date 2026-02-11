<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Employee;

/**
 * EmployeeSearch represents the model behind the search form for Employee
 * 
 * Provides search and filter functionality for the employee list
 * with support for:
 * - Text search across multiple fields
 * - Salary range filtering
 * - Sorting and pagination
 * 
 * @author Your Name
 * @since 1.0
 */
class EmployeeSearch extends Employee
{
    // Additional search attributes
    public $salary_min;
    public $salary_max;
    public $created_from;
    public $created_to;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            // Integer fields
            [['id'], 'integer'],
            
            // String fields - safe for search
            [['name', 'email', 'position'], 'safe'],
            
            // Numeric fields
            [['salary', 'salary_min', 'salary_max'], 'number'],
            
            // Date fields
            [['created_at', 'updated_at', 'created_from', 'created_to'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params Search parameters from request
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Employee::find();

        // Configure data provider with sorting and pagination
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 10, // Items per page
            ],
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC, // Newest first by default
                ],
                'attributes' => [
                    'id',
                    'name',
                    'email',
                    'position',
                    'salary',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);

        // Load search parameters
        $this->load($params);

        // If validation fails, return empty results
        if (!$this->validate()) {
            // Uncomment to return all records even when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // Apply filters based on search parameters
        $this->applyFilters($query);

        return $dataProvider;
    }

    /**
     * Apply search filters to the query
     * 
     * @param \yii\db\ActiveQuery $query
     */
    protected function applyFilters($query)
    {
        // Filter by ID (exact match)
        $query->andFilterWhere(['id' => $this->id]);

        // Filter by text fields (partial match, case-insensitive)
        $query->andFilterWhere(['like', 'name', $this->name])
              ->andFilterWhere(['like', 'email', $this->email])
              ->andFilterWhere(['like', 'position', $this->position]);

        // Filter by salary (exact match)
        $query->andFilterWhere(['salary' => $this->salary]);

        // Filter by salary range
        if ($this->salary_min !== null && $this->salary_min !== '') {
            $query->andWhere(['>=', 'salary', $this->salary_min]);
        }
        
        if ($this->salary_max !== null && $this->salary_max !== '') {
            $query->andWhere(['<=', 'salary', $this->salary_max]);
        }

        // Filter by date range
        if ($this->created_from !== null && $this->created_from !== '') {
            $query->andWhere(['>=', 'created_at', $this->created_from . ' 00:00:00']);
        }
        
        if ($this->created_to !== null && $this->created_to !== '') {
            $query->andWhere(['<=', 'created_at', $this->created_to . ' 23:59:59']);
        }

        // Filter by created_at and updated_at (exact date match)
        $query->andFilterWhere(['like', 'created_at', $this->created_at])
              ->andFilterWhere(['like', 'updated_at', $this->updated_at]);
    }

    /**
     * Get employees with high salary (example custom search)
     * 
     * @param float $threshold Salary threshold
     * @return ActiveDataProvider
     */
    public function searchHighEarners($threshold = 80000)
    {
        $query = Employee::find()
            ->where(['>=', 'salary', $threshold])
            ->orderBy(['salary' => SORT_DESC]);

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 10],
        ]);
    }

    /**
     * Search by position
     * 
     * @param string $position
     * @return ActiveDataProvider
     */
    public function searchByPosition($position)
    {
        $query = Employee::find()
            ->where(['position' => $position])
            ->orderBy(['name' => SORT_ASC]);

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 10],
        ]);
    }

    /**
     * Get attribute labels for search form
     * 
     * @return array
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'salary_min' => 'Minimum Salary',
            'salary_max' => 'Maximum Salary',
            'created_from' => 'Created From',
            'created_to' => 'Created To',
        ]);
    }
}