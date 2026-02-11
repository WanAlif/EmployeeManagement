<?php

namespace app\controllers;

use Yii;
use app\models\Employee;
use app\models\EmployeeSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\Response;

/**
 * EmployeeController implements the CRUD actions for Employee model.
 * 
 * Handles all employee management operations including:
 * - Listing employees with search/filter
 * - Creating new employees
 * - Viewing employee details
 * - Updating employee information
 * - Deleting employees
 * 
 * @author Wan
 * @since 1.0
 */
class EmployeeController extends Controller
{
    /**
     * Configure controller behaviors
     * 
     * @return array
     */
    public function behaviors()
    {
        return [
            // Verb filter restricts HTTP methods for certain actions
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'], // Delete only via POST for security
                ],
            ],
            
            // Uncomment to add access control (authentication)
            // 'access' => [
            //     'class' => AccessControl::class,
            //     'rules' => [
            //         [
            //             'allow' => true,
            //             'roles' => ['@'], // Only authenticated users
            //         ],
            //     ],
            // ],
        ];
    }

    /**
     * Lists all Employee models with search and pagination
     * 
     * @return string The rendered view
     */
    public function actionIndex()
    {
        $searchModel = new EmployeeSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Employee model
     * 
     * @param int $id Employee ID
     * @return string The rendered view
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        
        return $this->render('view', [
            'model' => $model,
        ]);
    }

    /**
     * Creates a new Employee model
     * 
     * If creation is successful, redirects to the 'view' page.
     * 
     * @return string|Response
     */
    public function actionCreate()
    {
        $model = new Employee(['scenario' => Employee::SCENARIO_CREATE]);

        if ($this->loadAndSave($model)) {
            Yii::$app->session->setFlash('success', 'Employee created successfully!');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Employee model
     * 
     * If update is successful, redirects to the 'view' page.
     * 
     * @param int $id Employee ID
     * @return string|Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $model->scenario = Employee::SCENARIO_UPDATE;

        if ($this->loadAndSave($model)) {
            Yii::$app->session->setFlash('success', 'Employee updated successfully!');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Employee model
     * 
     * If deletion is successful, redirects to the 'index' page.
     * 
     * @param int $id Employee ID
     * @return Response
     * @throws NotFoundHttpException if the model cannot be found
     * @throws \yii\db\Exception if deletion fails
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        
        try {
            if ($model->delete()) {
                Yii::$app->session->setFlash('success', 'Employee deleted successfully!');
            } else {
                Yii::$app->session->setFlash('error', 'Failed to delete employee.');
            }
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', 'An error occurred while deleting the employee: ' . $e->getMessage());
            Yii::error($e->getMessage(), __METHOD__);
        }

        return $this->redirect(['index']);
    }

    /**
     * Export employees to CSV format
     * 
     * @return Response
     */
    public function actionExport()
    {
        $employees = Employee::find()->orderBy(['id' => SORT_ASC])->all();
        
        $filename = 'employees_' . date('Y-m-d_His') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV Headers
        fputcsv($output, ['ID', 'Name', 'Email', 'Position', 'Salary', 'Created At', 'Updated At']);
        
        // CSV Data
        foreach ($employees as $employee) {
            fputcsv($output, [
                $employee->id,
                $employee->name,
                $employee->email,
                $employee->position,
                $employee->salary,
                $employee->created_at,
                $employee->updated_at,
            ]);
        }
        
        fclose($output);
        exit;
    }

    /**
     * Get employee statistics (AJAX)
     * 
     * @return array JSON response
     */
    public function actionStatistics()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        try {
            return [
                'success' => true,
                'data' => Employee::getStatistics(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to fetch statistics: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Finds the Employee model based on its primary key value
     * 
     * @param int $id Employee ID
     * @return Employee the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Employee::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested employee does not exist.');
    }

    /**
     * Load and save model with error handling
     * 
     * @param Employee $model
     * @return bool whether the model was saved successfully
     */
    protected function loadAndSave($model)
    {
        if ($model->load(Yii::$app->request->post())) {
            try {
                if ($model->save()) {
                    return true;
                } else {
                    // Log validation errors
                    Yii::error('Validation errors: ' . json_encode($model->errors), __METHOD__);
                    
                    // Set flash message with first error
                    $firstError = array_values($model->getFirstErrors())[0] ?? 'Validation failed.';
                    Yii::$app->session->setFlash('error', $firstError);
                }
            } catch (\Exception $e) {
                Yii::error('Save error: ' . $e->getMessage(), __METHOD__);
                Yii::$app->session->setFlash('error', 'An error occurred while saving: ' . $e->getMessage());
            }
        }

        return false;
    }

    /**
     * Batch delete employees (example of additional functionality)
     * 
     * @return Response
     */
    public function actionBatchDelete()
    {
        $ids = Yii::$app->request->post('ids', []);
        
        if (empty($ids)) {
            Yii::$app->session->setFlash('error', 'No employees selected for deletion.');
            return $this->redirect(['index']);
        }

        $transaction = Yii::$app->db->beginTransaction();
        
        try {
            $deleted = Employee::deleteAll(['id' => $ids]);
            $transaction->commit();
            
            Yii::$app->session->setFlash('success', "$deleted employee(s) deleted successfully!");
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error('Batch delete error: ' . $e->getMessage(), __METHOD__);
            Yii::$app->session->setFlash('error', 'Failed to delete employees: ' . $e->getMessage());
        }

        return $this->redirect(['index']);
    }
}