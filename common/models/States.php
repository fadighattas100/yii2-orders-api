<?php

namespace common\models;

use common\helpers\Helpers;
use Yii;

/**
 * This is the model class for table "states".
 *
 * @property string $id
 * @property string $name
 * @property string $deleted_at
 * @property string $created_at
 * @property string $updated_at
 * @property string $country_id
 *
 * @property Areas[] $areas
 * @property Countries $country
 */
class States extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'states';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['deleted_at', 'created_at', 'updated_at'], 'safe'],
            [['country_id'], 'integer'],
            [['name'], 'string', 'max' => 255],
            [['name'], 'unique'],
            [['country_id'], 'exist', 'skipOnError' => true, 'targetClass' => Countries::className(), 'targetAttribute' => ['country_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'deleted_at' => 'Deleted At',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'country_id' => 'Country ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAreas()
    {
        return $this->hasMany(Areas::className(), ['state_id' => 'id'])->select(['id', 'name'])->asArray()->all();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCountry()
    {
        return $this->hasOne(Countries::className(), ['id' => 'country_id']);
    }


    /**
     * @inheritdoc
     * @return StatesQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new StatesQuery(get_called_class());
    }

    public static function getStates()
    {
        $headers = Yii::$app->getRequest()->getHeaders();
        if (!isset($headers['country_id']))
            return Helpers::HttpException(422, 'validation failed', ['error' => 'country_id is required']);
        if (empty($headers['country_id']))
            return Helpers::HttpException(422, 'validation failed', ['error' => "country_id can't be blank"]);
        if (!is_int(intval($headers['country_id'])))
            return Helpers::HttpException(422, 'validation failed', ['error' => "country_id must be integer"]);

        $States = self::find()->where(['country_id' => $headers['country_id']])->all();

        return Helpers::formatResponse(true, 'get success', $States);
    }

    public function afterValidate()
    {
        if ($this->hasErrors()) {
            return Helpers::HttpException(422, 'validation failed', ['error' => $this->errors]);
        }
    }

    public function beforeSave($insert)
    {
        if (!$this->isNewRecord)
            $this->updated_at = date('Y-m-d H:i:s');
        else
            $this->created_at = date('Y-m-d H:i:s');

        return parent::beforeSave($insert); // TODO: Change the autogenerated stub
    }

    public function fields()
    {
        return [
            'id',
            'name',
            'country' => function () {
                return $this->country;
            },
            'areas' => function () {
                return $this->areas;
            }
        ];

    }
}
