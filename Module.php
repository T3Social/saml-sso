<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\sso\saml;

use humhub\modules\sso\saml\models\Settings;
use yii\helpers\Url;

class Module extends \humhub\components\Module
{

    private $_settingsModel = null;


    /**
     * @var array Advanced settings for the UI configured SAML client
     * @see https://github.com/onelogin/php-saml/blob/master/advanced_settings_example.php
     */
    public $advancedSettings = [];

    /**
     * @inheritdocs
     */
    public $resourcesPath = 'resources';


    /**
     * @inheritdocs
     */
    public function getConfigUrl()
    {
        return Url::to(['/saml-sso/admin']);
    }


    /**
     * @return Settings
     */
    public function getSettingsModel()
    {
        if ($this->_settingsModel === null) {
            $this->_settingsModel = new Settings();
            $this->_settingsModel->loadSettings();
        }

        return $this->_settingsModel;
    }


}