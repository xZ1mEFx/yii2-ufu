<?php
use xz1mefx\ufu\models\UfuCategory;
use yii\bootstrap\Html;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\grid\SerialColumn;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel xz1mefx\ufu\models\search\UfuCategorySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $type integer|null */
/* @var $canAdd bool */
/* @var $canUpdate bool */
/* @var $canDelete bool */

$this->title = Yii::t('ufu-tools', 'Categories');
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="ufu-category-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php if ($canAdd): ?>
        <p>
            <?= Html::a(Yii::t('ufu-tools', 'Create Category'), ['create'], ['class' => 'btn btn-success']) ?>
        </p>
    <?php endif; ?>

    <?php if ($canDelete): ?>
        <p class="text-info">
            <strong><?= Html::icon('info-sign') ?> <?= Yii::t('ufu-tools', 'Warning:') ?></strong>
            <?= Yii::t('ufu-tools', 'You can delete the category only without relations, parents and children') ?>
        </p>
    <?php endif; ?>

    <?php Pjax::begin(); ?>
    <?= GridView::widget([
        'tableOptions' => ['class' => 'table table-striped table-bordered table-hover'],
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => SerialColumn::className()],

//            [
//                'attribute' => 'id',
//                'headerOptions' => ['class' => 'col-xs-1 col-sm-1'],
//                'contentOptions' => ['class' => 'col-xs-1 col-sm-1'],
//            ],
            [
                'attribute' => 'is_section',
                'filter' => FALSE,
                'content' => function ($model) {
                    /* @var $model UfuCategory */
                    return $model->is_section ? Html::icon('ok') : '';
                },
                'headerOptions' => ['class' => 'col-xs-1 col-sm-1'],
                'contentOptions' => ['class' => 'col-xs-1 col-sm-1'],
                'visible' => $canSetSection,
            ],
            [
                'attribute' => 'type',
                'filter' => Yii::$app->ufu->getDrDownUrlTypes(),
                'content' => function ($model) {
                    /* @var $model UfuCategory */
                    return (Yii::$app->ufu->getTypeNameById($model->type));
                },
                'visible' => !$type,
            ],
            [
                'attribute' => 'parentName',
                'format' => 'raw',
            ],
            [
                'attribute' => 'name',
                'format' => 'raw',
            ],
            [
                'attribute' => 'url',
                'format' => 'raw',
            ],
            [
                'attribute' => 'relationsCount',
                'headerOptions' => ['class' => 'col-xs-1 col-sm-1'],
                'contentOptions' => ['class' => 'col-xs-1 col-sm-1'],
            ],
            [
                'attribute' => 'parentsCount',
                'headerOptions' => ['class' => 'col-xs-1 col-sm-1'],
                'contentOptions' => ['class' => 'col-xs-1 col-sm-1'],
            ],
            [
                'attribute' => 'childrenCount',
                'headerOptions' => ['class' => 'col-xs-1 col-sm-1'],
                'contentOptions' => ['class' => 'col-xs-1 col-sm-1'],
            ],

            [
                'class' => ActionColumn::className(),
                'visible' => $canUpdate || $canDelete,
                'headerOptions' => ['class' => 'text-center col-xs-1 col-sm-1'],
                'contentOptions' => ['class' => 'text-center col-xs-1 col-sm-1'],
                'template' => '{view} {update} {delete}',
                'visibleButtons' => [
                    'update' => $canUpdate,
                    'delete' => function ($model, $key, $index) use ($canDelete) {
                        /* @var $model UfuCategory */
                        return $canDelete && $model->canDelete;
                    },
                ],
            ],
        ],
    ]); ?>
    <?php Pjax::end(); ?>

</div>
