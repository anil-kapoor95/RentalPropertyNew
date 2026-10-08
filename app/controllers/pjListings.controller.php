<?php

class pjListings extends pjFront
{

	/**
	 * Data for the properties listing (shared by the page view and the JS embed).
	 */
	public function getListingItems()
	{
		$locale = $this->getLocaleId();
		$calendars = pjCalendarModel::factory()
			->select("t1.id, t1.bedrooms, t1.bathrooms, t1.address_state, t2.content AS name, t3.content AS city")
			->join('pjMultiLang', "t2.foreign_id = t1.id AND t2.model = 'pjCalendar' AND t2.locale = '".$locale."' AND t2.field = 'name'", 'left')
			->join('pjMultiLang', "t3.foreign_id = t1.id AND t3.model = 'pjCalendar' AND t3.locale = '".$locale."' AND t3.field = 'address_city'", 'left')
			->where('t1.status', 'T')
			// Only the properties of the requested company (?company=ID).
			->where('t1.company_id', (int) $this->frontCompanyId)
			->orderBy('t2.content ASC')
			->findAll()->getData();

		$photoModel = pjCalendarPhotoModel::factory();
		$items = array();
		foreach ($calendars as $c)
		{
			$photos = $photoModel->getByCalendar($c['id']);
			$img = (!empty($photos) && !empty($photos[0]['large_path']) && is_file(PJ_INSTALL_PATH . $photos[0]['large_path'])) ? $photos[0]['large_path'] : '';
			$items[] = array(
				'id'    => (int) $c['id'],
				'name'  => stripslashes((string) $c['name']),
				'city'  => trim(strip_tags((string) $c['city'])),
				'state' => trim((string) $c['address_state']),
				'beds'  => ($c['bedrooms']  !== null && $c['bedrooms']  !== '' && $c['bedrooms']  !== 'na') ? (int) $c['bedrooms']  : null,
				'baths' => ($c['bathrooms'] !== null && $c['bathrooms'] !== '' && $c['bathrooms'] !== 'na') ? (int) $c['bathrooms'] : null,
				'img'   => $img,
			);
		}
		return $items;
	}

	/**
	 * Heading of the listing: the company name, else the global company option.
	 */
	private function getListingTitle()
	{
		if ($this->frontCompanyId > 0)
		{
			$c = pjCompanyModel::factory()->find($this->frontCompanyId)->getData();
			if (!empty($c['name']))
			{
				return $c['name'];
			}
		}
		return isset($this->option_arr['o_company']) ? $this->option_arr['o_company'] : '';
	}

	/**
	 * All-properties page.
	 *   index.php?controller=pjListings&action=pjActionListing
	 */
	public function pjActionListing()
	{
		$this->set('items', $this->getListingItems());
		$this->set('company', $this->getListingTitle());
		$this->set('base', PJ_INSTALL_URL);
		$this->set('cssUrl', PJ_INSTALL_URL . 'app/web/css/rpbc-modern.css?v=' . @filemtime(PJ_INSTALL_PATH . 'app/web/css/rpbc-modern.css'));
		$this->set('themeCss', PJ_INSTALL_URL . 'index.php?controller=pjFront&action=pjActionThemeCss&company=' . $this->getFrontCompanyHash());
		$this->setLayout('pjActionEmpty');
	}

	/**
	 * Single property page. The booking widget is embedded natively via pjFront::pjActionLoad.
	 *   index.php?controller=pjListings&action=pjActionListingView&cid=X
	 */
	public function pjActionListingView()
	{
		$cid = $this->_get->toInt('cid');
		$loc = $this->getLocaleId();
		$base = PJ_INSTALL_URL;

		$rows = pjCalendarModel::factory()
			->select("t1.*, t2.content AS m_name, t3.content AS m_description, t4.content AS m_city, t5.content AS m_meta_title, t6.content AS m_meta_keywords, t7.content AS m_meta_description, t8.content AS m_google_address")
			->join('pjMultiLang', "t2.model='pjCalendar' AND t2.foreign_id=t1.id AND t2.field='name' AND t2.locale='".$loc."'", 'left outer')
			->join('pjMultiLang', "t3.model='pjCalendar' AND t3.foreign_id=t1.id AND t3.field='description' AND t3.locale='".$loc."'", 'left outer')
			->join('pjMultiLang', "t4.model='pjCalendar' AND t4.foreign_id=t1.id AND t4.field='address_city' AND t4.locale='".$loc."'", 'left outer')
			->join('pjMultiLang', "t5.model='pjCalendar' AND t5.foreign_id=t1.id AND t5.field='meta_title' AND t5.locale='".$loc."'", 'left outer')
			->join('pjMultiLang', "t6.model='pjCalendar' AND t6.foreign_id=t1.id AND t6.field='meta_keywords' AND t6.locale='".$loc."'", 'left outer')
			->join('pjMultiLang', "t7.model='pjCalendar' AND t7.foreign_id=t1.id AND t7.field='meta_description' AND t7.locale='".$loc."'", 'left outer')
			->join('pjMultiLang', "t8.model='pjCalendar' AND t8.foreign_id=t1.id AND t8.field='google_address' AND t8.locale='".$loc."'", 'left outer')
			->where('t1.id', $cid)->where('t1.status', 'T')->limit(1)->findAll()->getData();

		if (empty($rows)) { $this->set('notFound', true); $this->set('cssUrl', $base . 'app/web/css/rpbc-modern.css?v=' . @filemtime(PJ_INSTALL_PATH . 'app/web/css/rpbc-modern.css')); $this->setLayout('pjActionEmpty'); return; }
		$c = $rows[0];

		$photos = pjCalendarPhotoModel::factory()->getByCalendar($cid);
		$gallery = array();
		foreach ($photos as $ph)
		{
			$large = (!empty($ph['large_path']) && is_file(PJ_INSTALL_PATH . $ph['large_path'])) ? $ph['large_path'] : '';
			$small = (!empty($ph['small_path']) && is_file(PJ_INSTALL_PATH . $ph['small_path'])) ? $ph['small_path'] : $large;
			if ($small !== '' || $large !== '') { $gallery[] = array('small' => ($small !== '' ? $small : $large), 'large' => ($large !== '' ? $large : $small)); }
		}

		$linkedIds = pjCalendarFeatureModel::factory()->where('calendar_id', $cid)->findAll()->getDataPair('feature_id', 'feature_id');
		$fetchFeatures = function ($categoryId) use ($loc, $linkedIds) {
			$all = pjFeatureModel::factory()->reset()->select('t1.id, t2.content AS name')
				->join('pjMultiLang', "t2.model='pjFeature' AND t2.foreign_id=t1.id AND t2.field='name' AND t2.locale='".$loc."'", 'left outer')
				->where('t1.status', 'T')->where('t1.category_id', $categoryId)->orderBy('`name` ASC')->findAll()->getData();
			$out = array();
			foreach ($all as $f) { if (isset($linkedIds[$f['id']])) { $out[] = stripslashes((string) $f['name']); } }
			return $out;
		};

		$lat = trim((string) $c['lat']);
		$lng = trim((string) $c['lng']);

		$this->set('prop', array(
			'id'      => (int) $c['id'],
			'name'    => stripslashes((string) $c['m_name']),
			'desc'    => trim((string) $c['m_description']),
			'city'    => trim(strip_tags((string) $c['m_city'])),
			'state'   => trim((string) $c['address_state']),
			'address' => trim(strip_tags((string) $c['m_google_address'])),
			'beds'    => ($c['bedrooms']  !== null && $c['bedrooms']  !== '' && $c['bedrooms']  !== 'na') ? (int) $c['bedrooms']  : null,
			'baths'   => ($c['bathrooms'] !== null && $c['bathrooms'] !== '' && $c['bathrooms'] !== 'na') ? (int) $c['bathrooms'] : null,
			'lat'     => $lat,
			'lng'     => $lng,
			'showMap' => ($lat !== '' && $lng !== '' && is_numeric($lat) && is_numeric($lng)),
		));
		$this->set('gallery', $gallery);
		$this->set('seo', array(
			'title'       => trim(strip_tags((string) $c['m_meta_title'])),
			'keywords'    => trim(strip_tags((string) $c['m_meta_keywords'])),
			'description' => trim(strip_tags((string) $c['m_meta_description'])),
		));
		$this->set('propFeat', $fetchFeatures(2));
		$this->set('commFeat', $fetchFeatures(1));
		$this->set('mapKey', isset($this->option_arr['o_google_maps_api_key']) ? trim((string) $this->option_arr['o_google_maps_api_key']) : '');
		$this->set('base', $base);
		$this->set('cssUrl', $base . 'app/web/css/rpbc-modern.css?v=' . @filemtime(PJ_INSTALL_PATH . 'app/web/css/rpbc-modern.css'));
		$this->set('themeCss', $base . 'index.php?controller=pjFront&action=pjActionThemeCss&cid=' . (int) $cid);
		$this->set('loadCss', $base . 'index.php?controller=pjFront&action=pjActionLoadCss&cid=' . (int) $cid);
		$this->set('loadJs', $base . 'index.php?controller=pjFront&action=pjActionLoad&cid=' . (int) $cid . '&view=1');
		$this->set('listUrl', $base . 'index.php?controller=pjListings&action=pjActionListing&company=' . $this->getFrontCompanyHash());
		$this->setLayout('pjActionEmpty');
	}

	/**
	 * Serve the static modern stylesheet.
	 *   <link rel="stylesheet" href="index.php?controller=pjListings&action=pjActionListingCss">
	 */
	public function pjActionListingCss()
	{
		while (ob_get_level() > 0) { ob_end_clean(); }
		header("Content-Type: text/css; charset=utf-8");
		header("Cache-Control: max-age=600, public");
		$file = PJ_INSTALL_PATH . 'app/web/css/rpbc-modern.css';
		if (is_file($file)) { readfile($file); }
		exit;
	}

	/**
	 * JS embed loader (view outputs the JS).
	 *   <script src="index.php?controller=pjListings&action=pjActionListingJs"></script>
	 */
	public function pjActionListingJs()
	{
		header("Content-Type: text/javascript; charset=utf-8");
		$base  = PJ_INSTALL_URL;
		$this->set('items', $this->getListingItems());
		$this->set('base', $base);
		$this->set('company', $this->getListingTitle());
		$this->set('themeCss', $base . 'index.php?controller=pjFront&action=pjActionThemeCss&company=' . $this->getFrontCompanyHash());
		$this->set('listCss', $base . 'app/web/css/rpbc-modern.css?v=' . @filemtime(PJ_INSTALL_PATH . 'app/web/css/rpbc-modern.css'));
		$this->setLayout('pjActionEmpty');
	}
}