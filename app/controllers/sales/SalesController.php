<?php

namespace app\controllers\sales;

use Yii;
use app\models\sales\Sales;
use app\models\sales\searchs\Sales as SalesSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use biz\core\sales\components\Sales as ApiSales;

/**
 * SalesController implements the CRUD actions for Sales model.
 */
class SalesController extends Controller
{

    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                    'confirm' => ['post']
                ],
            ],
        ];
    }

    /**
     * Lists all Sales models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new SalesSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Sales model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);

        return $this->render('view', [
                'model' => $model,
        ]);
    }

    /**
     * Creates a new Sales model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Sales([
            'branch_id' => 1
        ]);
        $api = new ApiSales([
            'modelClass' => Sales::className(),
        ]);

        if ($model->load(Yii::$app->request->post())) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $data = $model->attributes;
                $data['details'] = Yii::$app->request->post('SalesDtl', []);
                $model = $api->create($data, $model);
                if (!$model->hasErrors()) {
                    $transaction->commit();
                    return $this->redirect(['view', 'id' => $model->id]);
                } else {
                    $transaction->rollBack();
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                throw $e;
            }
        }
        return $this->render('create', [
                'model' => $model,
        ]);
    }

    /**
     * Updates an existing Sales model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $api = new ApiSales([
            'modelClass' => Sales::className(),
        ]);

        if ($model->status > Sales::STATUS_DRAFT) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        if ($model->load(Yii::$app->request->post())) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $data = $model->attributes;
                $data['details'] = Yii::$app->request->post('SalesDtl', []);
                $model = $api->update($id, $data, $model);
                if (!$model->hasErrors()) {
                    $transaction->commit();
                    return $this->redirect(['view', 'id' => $model->id]);
                } else {
                    $transaction->rollBack();
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                throw $e;
            }
        }
        return $this->render('update', [
                'model' => $model,
        ]);
    }

    public function actionConfirm($id)
    {
        $model = $this->findModel($id);
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $model->status = Sales::STATUS_CONFIRMED;
            if ($model->save()) {
                $transaction->commit();
                return $this->redirect(['view', 'id' => $id]);
            } else {
                $transaction->rollBack();
                throw new \yii\base\UserException(implode(",\n", $model->firstErrors));
            }
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public function actionRelease($id)
    {
        return $this->redirect(['/inventory/movement/create', 'type' => 200, 'id' => $id]);
    }

    /**
     * Deletes an existing Sales model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $api = new ApiSales([
            'modelClass' => Sales::className(),
        ]);
        $api->delete($id, $model);
        return $this->redirect(['index']);
    }

    /**
     * Finds the Sales model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Sales the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Sales::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}