<?php
if (!defined("ROOT_PATH")) {
    header("HTTP/1.1 403 Forbidden");
    exit;
}

/**
 * Post-install setup wizard: import or skip demo properties for the
 * Rental Property Booking Calendar. Demo properties are identified by the
 * uuid prefix DEMO_UUID_PREFIX and can be removed again from the admin.
 */
class pjAdminSetup extends pjAdmin
{
    const DEMO_UUID_PREFIX = 'rpbcdemo';

    /** Demo properties: uuid, name, description, city, state, zip, lat, lng, beds, baths, feature ids, image */
    private static function demoProperties()
    {
        return array(
            array('rpbcdemo01', 'Downtown Loft', 'A bright downtown loft with open-plan living, walking distance to restaurants, transit and nightlife. Ideal for city breaks.', 'San Francisco', 'California', '94103', '37.7749', '-122.4194', '2', '2', array(1, 7, 10, 15, 22), 'prop1.svg'),
            array('rpbcdemo02', 'Beachside Bungalow', 'Sunny beachside bungalow steps from the sand. Private patio, bright interiors and easy access to the boardwalk and cafes.', 'Miami', 'Florida', '33139', '25.7617', '-80.1918', '3', '2', array(2, 8, 11, 18, 25), 'prop2.svg'),
            array('rpbcdemo03', 'Mountain Cabin', 'Cozy mountain cabin surrounded by pines. Wood-burning stove, hiking trails at the door and stargazing from the deck.', 'Denver', 'Colorado', '80202', '39.7392', '-104.9903', '2', '1', array(3, 9, 19, 28, 31), 'prop3.svg'),
            array('rpbcdemo04', 'City Apartment', 'Modern one-bedroom apartment in the heart of the city. Close to subway lines, parks, museums and shopping.', 'New York', 'New York', '10001', '40.7128', '-74.0060', '1', '1', array(4, 12, 20, 37, 44), 'prop4.svg'),
            array('rpbcdemo05', 'Desert Villa', 'Spacious desert villa with a private pool and shaded courtyard. Perfect for families and groups seeking sun and space.', 'Phoenix', 'Arizona', '85004', '33.4484', '-112.0740', '4', '3', array(5, 13, 23, 40, 47), 'prop5.svg'),
            array('rpbcdemo06', 'Lakeside Cottage', 'Charming lakeside cottage with water views, a small dock and a quiet garden. Great for a relaxing weekend escape.', 'Seattle', 'Washington', '98101', '47.6062', '-122.3321', '2', '2', array(6, 14, 21, 42, 50), 'prop6.svg'),
        );
    }

    public function beforeFilter()
    {
        pjAppController::beforeFilter();
        return true;
    }

    // ---- Welcome / wizard page ----------------------------------------
    public function pjActionWelcome()
    {
        $this->checkLogin();
        if (!pjAuth::factory('pjAdminCalendars')->hasAccess()) {
            pjUtil::redirect(PJ_INSTALL_URL . 'index.php?controller=pjAdmin&action=pjActionIndex');
        }
        $this->set('hasDemoData', self::hasDemoData());
        $this->set('wizardCompleted', $this->isWizardCompleted());
        $this->appendJs('pjAdminSetup.js');
    }

    // ---- AJAX: import demo data ---------------------------------------
    public function pjActionLoadDemo()
    {
        $this->setAjax(true);
        if (!$this->isXHR() || !$this->isLoged()) {
            self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'Unauthorized.'));
        }
        try {
            @set_time_limit(300);
            $count = $this->createDemoProperties();
            $this->markWizardCompleted();
            self::jsonResponse(array('status' => 'OK', 'code' => 200, 'text' => 'Demo data imported.', 'data' => array('properties' => $count)));
        } catch (Exception $e) {
            self::jsonResponse(array('status' => 'ERR', 'code' => 500, 'text' => 'Could not import demo data. ' . $e->getMessage()));
        }
        exit;
    }

    // ---- AJAX: skip / start empty -------------------------------------
    public function pjActionSkipDemo()
    {
        $this->setAjax(true);
        if (!$this->isXHR() || !$this->isLoged()) {
            self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'Unauthorized.'));
        }
        $this->markWizardCompleted();
        self::jsonResponse(array('status' => 'OK', 'code' => 200, 'text' => 'Setup completed.'));
        exit;
    }

    // ---- AJAX: remove demo data ---------------------------------------
    public function pjActionRemoveDemo()
    {
        $this->setAjax(true);
        if (!$this->isXHR() || !$this->isLoged()) {
            self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'Unauthorized.'));
        }
        try {
            $removed = $this->removeDemoProperties();
            self::jsonResponse(array('status' => 'OK', 'code' => 200, 'text' => 'Demo data removed.', 'data' => array('properties' => $removed)));
        } catch (Exception $e) {
            self::jsonResponse(array('status' => 'ERR', 'code' => 500, 'text' => 'Could not remove demo data. ' . $e->getMessage()));
        }
        exit;
    }

    // ---- AJAX: does demo data exist? ----------------------------------
    public function pjActionHasDemoData()
    {
        $this->setAjax(true);
        if (!$this->isXHR() || !$this->isLoged()) {
            self::jsonResponse(array('status' => 'ERR', 'code' => 100));
        }
        self::jsonResponse(array('status' => 'OK', 'code' => 200, 'has_demo' => self::hasDemoData()));
        exit;
    }

    // ---- helpers ------------------------------------------------------
    public static function hasDemoData()
    {
        try {
            return pjCalendarModel::factory()->reset()->where('t1.uuid LIKE', self::DEMO_UUID_PREFIX . '%')->findCount()->getData() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    private function isWizardCompleted()
    {
        try {
            $arr = pjOptionModel::factory()->reset()->where("t1.`foreign_id` = 0 AND t1.`key` = 'o_setup_wizard_completed'")->limit(1)->findAll()->getData();
            return !empty($arr) && $arr[0]['value'] == '1';
        } catch (Exception $e) {
            return false;
        }
    }

    private function markWizardCompleted()
    {
        pjOptionModel::factory()->reset()->where('foreign_id', 0)->where('`key`', 'o_setup_wizard_completed')->eraseAll();
        pjOptionModel::factory()->reset()->setAttributes(array('foreign_id' => 0, 'key' => 'o_setup_wizard_completed', 'value' => '1', 'type' => 'string'))->insert();
    }

    private function createDemoProperties()
    {
        $locales = pjLocaleModel::factory()->reset()->findAll()->getDataPair(NULL, 'id');
        if (empty($locales)) {
            $locales = array(1);
        }
        $created = 0;
        $company_id = $this->getCompanyId();
        // Demo features refer to the default amenity list (ids 1..n); map them to this company's copy.
        $company_features = array_values(array_map('intval', pjFeatureModel::factory()->where('t1.company_id', $company_id)->orderBy('t1.id ASC')->findAll()->getDataPair(null, 'id')));
        foreach (self::demoProperties() as $p) {
            list($uuid, $name, $desc, $city, $state, $zip, $lat, $lng, $beds, $baths, $features, $image) = $p;

            // skip if this demo property already exists
            if (pjCalendarModel::factory()->reset()->where('t1.uuid', $uuid)->findCount()->getData() > 0) {
                continue;
            }

            $id = pjCalendarModel::factory()->reset()->setAttributes(array(
                'user_id'        => 1,
                'company_id'     => $company_id > 0 ? $company_id : ':NULL',
                'uuid'           => $uuid,
                'status'         => 'T',
                'bedrooms'       => $beds,
                'bathrooms'      => $baths,
                'address_state'  => $state,
                'address_zip'    => $zip,
                'lat'            => $lat,
                'lng'            => $lng,
                'show_googlemap' => 'T',
            ))->insert()->getInsertId();

            if ($id === false || (int) $id <= 0) {
                continue;
            }

            // per-calendar options (colours, booking form, etc.)
            $pjOptionModel = pjOptionModel::factory();
            $pjOptionModel->init($id);
            $pjOptionModel->initConfirmation($id, null);

            // name / description / city for every locale
            $data = array();
            foreach ($locales as $lid) {
                $data[(int) $lid] = array('name' => $name, 'description' => $desc, 'address_city' => $city);
            }
            pjMultiLangModel::factory()->saveMultiLang($data, $id, 'pjCalendar');

            // features
            foreach ($features as $fid) {
                if (!isset($company_features[(int) $fid - 1])) {
                    continue;
                }
                pjCalendarFeatureModel::factory()->reset()->setAttributes(array('calendar_id' => $id, 'feature_id' => $company_features[(int) $fid - 1]))->insert();
            }

            // base default-season price (random per property, same for every weekday)
            $price = rand(6, 40) * 10; // 60..400
            pjPriceModel::factory()->reset()->setAttributes(array(
                'foreign_id' => $id,
                'tab_id'     => 1,
                'season'     => __('prices_price_default', true),
                'date_from'  => null,
                'date_to'    => null,
                'adults'     => 0,
                'children'   => 0,
                'mon' => $price, 'tue' => $price, 'wed' => $price, 'thu' => $price,
                'fri' => $price, 'sat' => $price, 'sun' => $price,
            ))->insert();

            // photo
            $this->addDemoPhoto($id, $image);

            $created++;
        }
        return $created;
    }

    private function addDemoPhoto($calendar_id, $image)
    {
        $src = PJ_INSTALL_PATH . 'app/web/img/demo/' . $image;
        if (!is_file($src)) {
            return false;
        }
        $ext  = strtolower(pathinfo($image, PATHINFO_EXTENSION));
        $mime = $ext === 'svg' ? 'image/svg+xml' : 'image/' . $ext;
        $hash = md5(uniqid(rand(), true));
        $rel  = PJ_UPLOAD_PATH . 'calendar_photos/' . $calendar_id . '_' . $hash . '.' . $ext;
        $dstDir = PJ_INSTALL_PATH . PJ_UPLOAD_PATH . 'calendar_photos';
        if (!is_dir($dstDir)) {
            @mkdir($dstDir, 0755, true);
        }
        if (!@copy($src, PJ_INSTALL_PATH . $rel)) {
            return false;
        }
        pjCalendarPhotoModel::factory()->reset()->setAttributes(array(
            'calendar_id' => $calendar_id,
            'hash'        => $hash,
            'file_name'   => $image,
            'mime_type'   => $mime,
            'small_path'  => $rel,
            'large_path'  => $rel,
            'sort'        => 0,
            'created'     => date('Y-m-d H:i:s'),
        ))->insert();
        return true;
    }

    private function removeDemoProperties()
    {
        $ids = pjCalendarModel::factory()->reset()->where('t1.uuid LIKE', self::DEMO_UUID_PREFIX . '%')->where('t1.company_id', $this->getCompanyId())->findAll()->getDataPair(NULL, 'id');
        $removed = 0;
        if (is_array($ids)) {
            foreach ($ids as $id) {
                $id = (int) $id;
                if ($id <= 1) {
                    continue;
                }
                // delete photo files
                $photos = pjCalendarPhotoModel::factory()->reset()->where('calendar_id', $id)->findAll()->getData();
                if (is_array($photos)) {
                    foreach ($photos as $photo) {
                        foreach (array('large_path', 'small_path') as $k) {
                            if (!empty($photo[$k]) && is_file(PJ_INSTALL_PATH . $photo[$k])) {
                                @unlink(PJ_INSTALL_PATH . $photo[$k]);
                            }
                        }
                    }
                }
                pjCalendarModel::factory()->reset()->set('id', $id)->erase();
                pjOptionModel::factory()->reset()->where('foreign_id', $id)->eraseAll();
                pjMultiLangModel::factory()->reset()->where('model', 'pjCalendar')->where('foreign_id', $id)->eraseAll();
                pjMultiLangModel::factory()->reset()->where('model', 'pjOption')->where('foreign_id', $id)->eraseAll();
                pjCalendarFeatureModel::factory()->reset()->where('calendar_id', $id)->eraseAll();
                pjCalendarPhotoModel::factory()->reset()->where('calendar_id', $id)->eraseAll();
                pjFeedModel::factory()->reset()->where('calendar_id', $id)->eraseAll();
                pjPriceModel::factory()->reset()->where('foreign_id', $id)->eraseAll();
                $removed++;
            }
        }
        return $removed;
    }
}
