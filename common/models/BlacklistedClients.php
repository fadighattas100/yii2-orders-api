<?php

namespace common\models;

use common\helpers\Helpers;
use Yii;
use yii\web\NotFoundHttpException;

/**
 * This is the model class for table "blacklisted_clients".
 *
 * @property string $id
 * @property string $reason
 * @property string $restaurant_id
 * @property string $client_id
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 *
 * @property Clients $client
 * @property Restaurants $restaurant
 */
class BlacklistedClients extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'blacklisted_clients';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['reason', 'restaurant_id', 'client_id'], 'required'],
            [['reason'], 'string'],
            [['restaurant_id', 'client_id'], 'integer'],
            [['created_at', 'updated_at', 'deleted_at'], 'safe'],
            [['client_id'], 'exist', 'skipOnError' => true, 'targetClass' => Clients::className(), 'targetAttribute' => ['client_id' => 'id']],
            [['restaurant_id'], 'exist', 'skipOnError' => true, 'targetClass' => Restaurants::className(), 'targetAttribute' => ['restaurant_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'reason' => 'Reason',
            'restaurant_id' => 'Restaurant ID',
            'client_id' => 'Client ID',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'deleted_at' => 'Deleted At',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getClient()
    {
        return $this->hasOne(Clients::className(), ['id' => 'client_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRestaurant()
    {
        return $this->hasOne(Restaurants::className(), ['id' => 'restaurant_id']);
    }

    /**
     * @inheritdoc
     * @return BlacklistedClientsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new BlacklistedClientsQuery(get_called_class());
    }

    public static function getRestaurantBlacklistedClients()
    {
        $restaurant = Restaurants::checkRestaurantAccess();
        return Helpers::formatResponse(true, 'get success', $restaurant->blacklistedClients);
    }

    public static function createItemBlacklistedClient($data)
    {
        $restaurant = Restaurants::checkRestaurantAccess();

        if (!isset($data['client_id']))
            return Helpers::HttpException(422, 'validation failed', ['error' => 'client_id is required']);

        $Client = Clients::find()->where(['id' => $data['client_id']])->andWhere(['deleted_at' => null])->one();
        if (empty($Client))
            return Helpers::HttpException(404, 'create failed', ['error' => 'client not found']);
        if (!empty(BlacklistedClients::find()->where(['restaurant_id' => $restaurant->id])->andWhere(['client_id' => $Client->id])->one()))
            return Helpers::HttpException(422, 'validation failed', ['error' => 'This client is already blocked']);

        $BlacklistedClient = new BlacklistedClients();
        $model['BlacklistedClients'] = $data;
        $BlacklistedClient->load($model);
        $BlacklistedClient->restaurant_id = $restaurant->id;
        $BlacklistedClient->validate();

        $isCreated = $BlacklistedClient->save();
        if (!$isCreated)
            return Helpers::HttpException(422, 'create failed', null);
        return Helpers::formatResponse(true, 'create success', ['id' => $BlacklistedClient->id]);
    }

    public static function deleteBlacklistedClient($blacklisted_client_id)
    {
        $restaurant = Restaurants::checkRestaurantAccess();

        $BlacklistedClient = BlacklistedClients::find()->where(['restaurant_id' => $restaurant->id])->andWhere(['client_id' => $blacklisted_client_id])->andWhere(['deleted_at' => null])->one();
        if (empty($BlacklistedClient))
            return Helpers::HttpException(404, 'deleted failed', ['error' => "This blacklisted client dos't exist"]);

        $isDeleted = $BlacklistedClient->delete();

        if (!$isDeleted)
            return Helpers::HttpException(422, 'deleted failed', null);

        return Helpers::formatResponse(true, 'deleted success', null);
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
            'id' => function () {
                return $this->client->id;
            },
            'reason',
            'name' => function () {
                return $this->client->user->username;
            },
            'email' => function () {
                return $this->client->user->email;
            },
            'address' => function () {
                $address =  Addresses::find()->where(['client_id' => $this->client->id])->andWhere(['is_default' => 1])->andWhere(['deleted_at' => null])->one()['address'];
                if(empty($address))
                    return Addresses::find()->where(['client_id' => $this->client->id])->andWhere(['deleted_at' => null])->orderBy('created_at DESC')->one()['address'];
                return $address;
            }
        ];

    }
}
