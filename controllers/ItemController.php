<?php

namespace app\controllers;

use app\models\ItemUsers;
use app\models\StoreReq;
use app\models\User;
use app\models\Users;
use app\models\ItemDetail;
use Yii;
use app\models\Items;
use app\models\ItemSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\db\Query;
/**
 * ItemController implements the CRUD actions for Items model.
 */
class ItemController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
            'access' => [
                'class' => AccessControl::className(),
                'only'  => ['create','index','view','update','itemdetail','detailtask','taskinsert','myitem'],
                'rules' => [
                    //只有1级管理员有权限
                    [
                        'actions'       => ['create','index','view','update'],
                        'allow'         => true,
                        'roles'         => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            return Yii::$app->user->identity->status == '管理员';
                        }
                    ],
                    [
                        'actions'       => ['itemdetail','detailtask','taskinsert'],
                        'allow'         => true,
                        'roles'         => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            return Yii::$app->user->identity->status == '干部';
                        }
                    ],
                    [
                        'actions'       => ['myitem'],
                        'allow'         => true,
                        'roles'         => ['@'],
                    ],

                ],
            ],
        ];
    }

    /**
     * Lists all Items models.
     * @return mixed
     */
    public function actionIndex()
    {
        $this->layout='@app/views/layouts/column_r.php';
        $searchModel = new ItemSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Items model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        //获取成员
        $model = $this->findModel($id);
        $user=new ItemUsers();
        $members=$user->getItemMembers($model);
        $members=$user->getItemChineseName($members);
        //获取中文名
        $author=Items::findOne($id);
        $creater=Users::find()->where(['st_id'=>$author->create_by])->one();
        $updater=Users::find()->where(['st_id'=>$author->update_by])->one();
        if($creater && $updater){
           return $this->render('view', [
             'model' => $this->findModel($id),
             'creater'=>$creater,
             'updater'=>$updater,
             'members'=>$members,
            ]);
        }elseif($creater){
            return $this->render('view', [
             'model' => $this->findModel($id),
             'creater'=>$creater,
             'members'=>$members,
            ]);
        }else{
            return $this->render('view', [
            'model' => $this->findModel($id),
            'members'=>$members,
            ]);
        }
        
    }

    /**
     * Creates a new Items model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Items();
        $user=new ItemUsers();
        $allusers=Users::find()->where(['status'=>'干部'])->all();
        $model->create_by=Yii::$app->user->identity->st_id;
        $model->status='未完成';
        $model->create_at=date('Y-m-d H:i',time());
        if ($model->load(Yii::$app->request->post()) && $user->load(Yii::$app->request->post())) {
            $model->save(false);
            $user->insertMembers($user->st_id,$model->id);

            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('create', [
                'model' => $model,
                'user'=>$user,
                'allusers'=>$allusers,
            ]);
        }
    }

    /**
     * Updates an existing Items model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $user=new ItemUsers();
        $members=$user->getItemMembers($model);
//        print_r($members);die;
        $user->st_id=$members;
//        $allusers=Users::find()->all();
        $allusers=Users::find()->where(['status'=>'干部'])->all();
        $model->update_by=Yii::$app->user->identity->st_id;
//        $model->status=1;
        $model->update_at=date('Y-m-d H:i',time());
        if ($model->load(Yii::$app->request->post()) && $user->load(Yii::$app->request->post())) {
            $model->save(false);
            $user->insertMembers($user->st_id,$id);
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('update', [
                'model' => $model,
                'user'=>$user,
                'allusers'=>$allusers,
            ]);
        }
    }

    /**
     * Deletes an existing Items model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        $item_details=ItemDetail::deleteAll(['item_id'=>$id]);
        $item_users=ItemUsers::deleteAll(['item_id'=>$id]);
        $item_req=StoreReq::deleteAll(['item_id'=>$id]);
        return $this->redirect(['index']);
    }

    /**
     * 项目负责人的后台界面
     * @return string
     */
    public function  actionItemdetail(){
        $usernow=Yii::$app->user->identity->st_id;
        $items=ItemUsers::find()->where(['st_id'=>$usernow])->all();
        return $this->render('item_detail', [
                'items'=>$items,
        ]);
    }
    /**
     * 项目负责人后台查看任务详情
     */
    public function actionItemdetailview(){
        if(!isset($_GET['id'])||empty($_GET['id'])){
            echo '{"success":false,"msg":"请求失败"}';
            return false;
        }
        $itemModel=new Items();
        $result=$itemModel->showItemDetails($_GET['id']);
        echo $result;

    }

    /**
     * 申请完成项目
     * @return bool
     */
    public function actionChangestatus(){
        if(!isset($_POST['id'])||empty($_POST['id'])){
            echo '{"success":false,"msg":"请求失败"}';
            return false;
        }
        $id=$_POST['id'];
        $item=Items::findOne($id);
        $item->status='负责人申请完成';
        if($item->save()){
            echo '{"success":true,"msg":"申请成功"}';
        }
    }
    /**
     * 撤销申请完成项目（重置为未完成）
     * @return bool
     */
    public function actionChangestatusback(){
        if(!isset($_POST['id'])||empty($_POST['id'])){
            echo '{"success":false,"msg":"请求失败"}';
            return false;
        }
        $id=$_POST['id'];
        $item=Items::findOne($id);
        $item->status='未完成';
        if($item->save()){
            echo '{"success":true,"msg":"重置成功"}';
        }
    }
    /**
     * 任务分配界面
     * @param $item_id
     * @return string
     */
    public function  actionDetailtask($item_id){
        $allstore_req=StoreReq::getStores($item_id);

//        $allusers=Users::find()->all();
        $allusers=Users::find()->where(['or','status=:status1','status=:status2'],[':status1'=>'干部',':status2'=>'部员'])->all();

//        $allstore_req=StoreReq::find()->where(['item_id'=>$item_id])->all();
        $alreadyNum=ItemDetail::find()->where(['item_id'=>$item_id])->count();
        if($alreadyNum!=0){
            $detailTask=ItemDetail::find()->where(['item_id'=>$item_id])->asArray()->all();
            return $this->render('detail_task', [
                'item_id'=>$item_id,
                'allusers'=>$allusers,
                'alreadyNum'=>$alreadyNum,
                'detailTask'=>$detailTask,
                'allstore_req'=>$allstore_req,

            ]);
        }else{
            return $this->render('detail_task', [
                'item_id'=>$item_id,
                'allusers'=>$allusers
            ]);
        }

    }

    /**
     * 执行项目任务安排的插入
     */
    public function  actionTaskinsert(){
        if(!isset($_POST['item_id'])||!isset($_POST['task'])||!isset($_POST['member'])){
            echo "<script>alert('请填写完整')</script>";
            echo "<script>window.history.go(-1)</script>";
            return false;
        }
        $item_id=$_POST['item_id'];
        $tasks=$_POST['task'];
        $members=$_POST['member'];
        $already=ItemDetail::deleteAll(['item_id'=>$item_id]);
//        $already->delete();
        $items=new Items();
        $items->addDetailTask($item_id,$tasks,$members,$item_id);
        //物料申请插入
        if(isset($_POST['store_req'])&&trim($_POST['store_req'])!=''){
            $store=new StoreReq();
            $store->insertStoreReq($_POST['store_req'],$item_id);
        }

//        $success='success';
        $usernow=Yii::$app->user->identity->st_id;
        $items=ItemUsers::find()->where(['st_id'=>$usernow])->all();
        return $this->redirect(['itemdetail']);



    }


    /**
     * @return string
     * 我参与的项目
     */
    public function actionMyitem(){
        $itemmodel=new Items();
        $items=$itemmodel->getMyItems();

//        $details=
        $myitems=Items::find()->where(['id'=>$items])->all();

        return $this->render('myitem', [
            'myitems'=>$myitems,
        ]);
    }

    /**
     * ajax我参与的项目详细
     */
    public function actionMyitemdetail(){
        if(empty($_GET['itemid']||!isset($_GET['itemid']))){
            echo '{"success":false,"msg":"获取失败"}';
        }
        $itemid=$_GET['itemid'];
        $item=new Items();
        $result=$item->myItemDetail($itemid);
        echo $result;
    }




    public function actionMobile($id){
        // print_r(Yii::$app->user->identity->st_id );die;
        $info=(new Query)->from('items')->where(['id'=>$id])->one();
        $item=Items::findOne($id);
        $users=$item->persons;
        $members=array();
        foreach ($users as $value) {
            $members[]=$value['st_id'];
        }
        $info['members']=$members;
        $result=json_encode($info,JSON_UNESCAPED_UNICODE);


        // foreach ($info as $key => $value) {
        //     if(!is_array($value)){
        //         $info[$key]=htmlspecialchars($value); 
        //         // print_r($value);
        //     }
            
        // }

        $result=json_encode($info,JSON_UNESCAPED_UNICODE);
        print_r($result);
    }



    public function  actionMobileitemcreate($title,$content,$st_id,$publisher){
        $connection = \Yii::$app->db;
        try {
            $model = new Items();
            $user=new ItemUsers();
            $allusers=Users::find()->all();
            $model->title=$title;
            $model->content=$content;
            $model->create_by=$publisher;
            $model->status='未完成';
            $model->create_at=date('Y-m-d H:i',time());
            $model->save(false);
            $memberArray=explode(',',$st_id);
            $user->insertMembers($memberArray,$model->id);
            $transaction = $connection->beginTransaction();
            $transaction->commit();
        } catch(Exception $e) {
            $transaction->rollBack();
        }



    }

    /**
     * Finds the Items model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Items the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Items::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
