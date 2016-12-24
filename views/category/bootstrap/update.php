<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model xz1mefx\ufu\models\UfuCategory */

$this->title = Yii::t('ufu', 'Update {modelClass}: ', [
        'modelClass' => 'Ufu Category',
    ]) . $model->id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('ufu', 'Ufu Categories'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('ufu', 'Update');
?>
<div class="ufu-category-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>