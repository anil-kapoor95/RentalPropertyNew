<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
class pjBaseLocale extends pjBase
{
	public $pjBaseLocaleKey = 'pjBaseLocaleKey';
	
	private $pjBaseLocaleError = 'pjBaseLocaleError';
	
	private function pjActionUpdateFieldsIndex()
	{
	    return pjBaseOptionModel::factory()
	    ->where('`key`', 'o_fields_index')
	    ->limit(1)
	    ->modifyAll(array('value' => md5(uniqid(rand(), true))))
	    ->getAffectedRows();
	}
	
	public function afterFilter()
	{
		$isFlagReady = false;
		$trace = debug_backtrace(0);
		foreach ($trace as $item)
		{
			if (isset($item['args'][0]['controller'], $item['args'][0]['action']) 
				&& $item['args'][0]['controller'] == 'pjBaseLocale' 
				&& $item['args'][0]['action'] == 'pjActionIsFlagReady')
			{
				$isFlagReady = true;
				break;
			}
		}
		
		if (!$isFlagReady)
		{
			parent::afterFilter();
		}
	}
	
	protected static function addLocale($iso, $locale_id=NULL)
	{
		$language = pjLocaleLanguageModel::factory()->where('t1.iso', $iso)->limit(1)->findAll()->getDataIndex(0);
	
		$pjLocaleModel = pjLocaleModel::factory();
	
		$pjLocaleModel->begin();
	
		$statement = sprintf("SET @sort := (SELECT MAX(`sort`) + 1 FROM `%s` LIMIT 1);", $pjLocaleModel->getTable());
		$pjLocaleModel->prepare($statement)->exec();
	
		$statement = sprintf("INSERT IGNORE INTO `%s` (`id`, `language_iso`, `name`, `dir`, `sort`, `is_default`) VALUES (NULL, :language_iso, :name, :dir, @sort, 0);", $pjLocaleModel->getTable());
		$insert_id = $pjLocaleModel
			->prepare($statement)
			->exec(array(
				'language_iso' => $iso,
				'name' => isset($language['native']) ? $language['native'] : 'NULL',
				'dir' => isset($language['dir']) ? $language['dir'] : 'NULL',
			))
			->getInsertId();
	
		if (!($insert_id !== FALSE && (int) $insert_id > 0))
		{
			return array('status' => 'ERR', 'code' => 100, 'text' => 'Locale has not been added.');
		}
	
		if (empty($locale_id))
		{
			$arr = $pjLocaleModel->reset()->findAll()->getData();
			foreach ($arr as $locale)
			{
				if ($locale['language_iso'] == 'gb')
				{
					$locale_id = $locale['id'];
					break;
				}
			}
			if (is_null($locale_id) && !empty($arr))
			{
				$locale_id = $arr[0]['id'];
			}
		}
	
		if (empty($locale_id))
		{
			$pjLocaleModel->rollback();
			return array('status' => 'ERR', 'code' => 101, 'text' => 'Locale ID is empty.');
		}
	
		$pjLocaleModel->commit();
	
		$pjMultiLangModel = pjMultiLangModel::factory();
	
		$sql = sprintf("INSERT IGNORE INTO `%1\$s` (`foreign_id`, `model`, `locale`, `field`, `content`)
			SELECT t1.foreign_id, t1.model, :insert_id, t1.field, t1.content
			FROM `%1\$s` AS t1
			WHERE t1.locale = :locale", $pjMultiLangModel->getTable());
	
		$pjMultiLangModel->prepare($sql)->exec(array(
			'insert_id' => $insert_id,
			'locale' => (int) $locale_id
		));
	
		return array('status' => 'OK', 'code' => 200, 'text' => 'Locale has been added.', 'id' => $insert_id);
	}
	
	protected static function checkDefault()
	{
		if (0 == pjLocaleModel::factory()->unscoped()->where('is_default', 1)->findCount()->getData())
		{
			pjLocaleModel::factory()->limit(1)->modifyAll(array('is_default' => 1));
		}
	}
	
	/* ------------------------------------------------------------------
	 * Languages per company
	 * ------------------------------------------------------------------ */

	/** Company whose language list is edited (0 = system list, e.g. no companies). */
	private function cLocaleCompany()
	{
		return class_exists('pjCompanyContext') && class_exists('pjCompanyLocaleModel') ? (int) pjCompanyContext::localeScopeId() : 0;
	}

	/** Make sure the company has exactly one default language. */
	private static function cCheckDefault($cid)
	{
		$m = pjCompanyLocaleModel::factory();
		if ((int) $m->where('t1.company_id', $cid)->where('t1.is_default', 1)->findCount()->getData() === 0)
		{
			$first = $m->reset()->where('t1.company_id', $cid)->orderBy('t1.sort ASC')->limit(1)->findAll()->getDataIndex(0);
			if (!empty($first))
			{
				$m->reset()->where('company_id', $cid)->where('locale_id', $first['locale_id'])->limit(1)->modifyAll(array('is_default' => 1));
			}
		}
	}

	/** Renumber the company's language order 1..n. */
	private static function cResort($cid)
	{
		$m = pjCompanyLocaleModel::factory();
		$rows = $m->where('t1.company_id', $cid)->orderBy('t1.sort ASC, t1.locale_id ASC')->findAll()->getData();
		foreach ($rows as $i => $r)
		{
			if ((int) $r['sort'] !== $i + 1)
			{
				$m->reset()->where('company_id', $cid)->where('locale_id', $r['locale_id'])->limit(1)->modifyAll(array('sort' => $i + 1));
			}
		}
	}

	/**
	 * Fill texts missing in language $to with the texts of language $from
	 * (only empty places are filled, nothing is overwritten).
	 */
	private static function cCopyTexts($from, $to, $cid = 0)
	{
		if ((int) $from <= 0 || (int) $to <= 0 || (int) $from === (int) $to)
		{
			return;
		}
		$pjMultiLangModel = pjMultiLangModel::factory();
		$scope = '';
		if ((int) $cid > 0)
		{
			// Only the texts of this company's records (plus the shared system texts):
			// the language may be used by other companies whose texts must not change.
			$cid = (int) $cid;
			$cal = sprintf("(SELECT `id` FROM `%s` WHERE `company_id` = %u)", pjCalendarModel::factory()->getTable(), $cid);
			$own = function ($model) use ($cid) { return sprintf("(SELECT `id` FROM `%s` WHERE `company_id` = %u)", $model->getTable(), $cid); };
			$scope = sprintf(" AND (
				(t1.model = 'pjBaseField' AND t1.field IN ('title', '%1\$s'))
				OR (t1.model IN ('pjCalendar', 'pjOption', 'pjPayment') AND t1.foreign_id IN %2\$s)
				OR (t1.model = 'pjReservation' AND t1.foreign_id IN (SELECT `id` FROM `%3\$s` WHERE `calendar_id` IN %2\$s))
				OR (t1.model = 'pjExtra' AND t1.foreign_id IN %4\$s)
				OR (t1.model = 'pjFeature' AND t1.foreign_id IN %5\$s)
				OR (t1.model = 'pjDiscount' AND t1.foreign_id IN %6\$s)
				OR (t1.model = 'pjVoucher' AND t1.foreign_id IN %7\$s)
				OR (t1.model = 'pjNotification' AND t1.foreign_id IN (SELECT `id` FROM `%8\$s` WHERE `company_id` = %9\$u OR `foreign_id` IN %2\$s))
				OR (t1.model = 'pjBaseOption' AND t1.foreign_id = %10\$u)
				OR (t1.model = 'pjBaseCountry' AND t1.foreign_id IN (SELECT `id` FROM `%11\$s` WHERE `company_id` IS NULL OR `company_id` = %9\$u))
			)",
				pjCompanyContext::labelField($cid), $cal, pjReservationModel::factory()->getTable(),
				$own(pjExtraModel::factory()), $own(pjFeatureModel::factory()), $own(pjDiscountModel::factory()), $own(pjVoucherModel::factory()),
				pjNotificationModel::factory()->getTable(), $cid, pjCompanyContext::optionsForeignId($cid), pjBaseCountryModel::factory()->getTable());
		}
		$sql = sprintf("INSERT IGNORE INTO `%1\$s` (`foreign_id`, `model`, `locale`, `field`, `content`)
			SELECT t1.foreign_id, t1.model, :to_locale, t1.field, t1.content
			FROM `%1\$s` AS t1
			WHERE t1.locale = :from_locale%2\$s", $pjMultiLangModel->getTable(), $scope);
		$pjMultiLangModel->prepare($sql)->exec(array('to_locale' => (int) $to, 'from_locale' => (int) $from));
	}

	/**
	 * System language for an ISO code, created if it does not exist yet
	 * (new languages start with the texts of $source_locale).
	 */
	private function cSystemLocale($iso, $source_locale)
	{
		$row = pjLocaleModel::factory()->unscoped()->where('t1.language_iso', $iso)->limit(1)->findAll()->getDataIndex(0);
		if (!empty($row))
		{
			return (int) $row['id'];
		}
		$result = self::addLocale($iso, $source_locale);
		return $result['status'] === 'OK' ? (int) $result['id'] : 0;
	}

	/** Remove a system language that no company uses any more. */
	private function cDropIfUnused($locale_id)
	{
		if (pjCompanyContext::localeUsage($locale_id) > 0)
		{
			return;
		}
		$arr = pjLocaleModel::factory()->unscoped()->find($locale_id)->getData();
		if (empty($arr))
		{
			return;
		}
		pjLocaleModel::factory()->set('id', $locale_id)->erase();
		self::removeFlagFile($arr);
		pjMultiLangModel::factory()->where('locale', $locale_id)->eraseAll();
		self::checkDefault();
	}

	/** Company: add a language (the first one it does not have yet). */
	private function cAddLocale($cid)
	{
		$own = pjCompanyContext::companyLocaleIds($cid);
		$own_iso = empty($own) ? array() : pjLocaleModel::factory()->unscoped()->whereIn('t1.id', $own)->findAll()->getDataPair(null, 'language_iso');
		$m = pjLocaleLanguageModel::factory()->where('t1.file IS NOT NULL');
		if (!empty($own_iso))
		{
			$m->whereNotIn('t1.iso', $own_iso);
		}
		$lang = $m->orderBy('t1.title ASC')->limit(1)->findAll()->getDataPair(null, 'iso');
		if (empty($lang))
		{
			return array('status' => 'ERR', 'code' => 100, 'text' => 'Locale has not been added.');
		}
		$default = pjCompanyContext::companyDefaultLocale($cid);
		$locale_id = $this->cSystemLocale($lang[0], $default);
		if ($locale_id <= 0)
		{
			return array('status' => 'ERR', 'code' => 100, 'text' => 'Locale has not been added.');
		}
		// texts of this company's records that do not exist yet in that language
		self::cCopyTexts($default, $locale_id, $cid);
		$m = pjCompanyLocaleModel::factory();
		$sorts = $m->where('t1.company_id', $cid)->findAll()->getDataPair(null, 'sort');
		$max = empty($sorts) ? 0 : max(array_map('intval', $sorts));
		$m->reset()->setAttributes(array('company_id' => $cid, 'locale_id' => $locale_id, 'sort' => $max + 1, 'is_default' => 0))->insert();
		return array('status' => 'OK', 'code' => 200, 'text' => 'Locale has been added.', 'id' => $locale_id);
	}

	/** Company: change which language a row of its list is (other companies keep theirs). */
	private function cChangeLocale($cid, $old_id, $iso)
	{
		$old = pjLocaleModel::factory()->unscoped()->find($old_id)->getData();
		if (empty($old) || $old['language_iso'] === $iso)
		{
			return;
		}
		$target = pjLocaleModel::factory()->unscoped()->where('t1.language_iso', $iso)->limit(1)->findAll()->getDataIndex(0);
		if (empty($target) && pjCompanyContext::localeUsage($old_id) <= 1)
		{
			// only this company uses the row: change it in place (as before)
			$tmp = pjLocaleLanguageModel::factory()->where('t1.iso', $iso)->limit(1)->findAll()->getDataIndex(0);
			pjLocaleModel::factory()->set('id', $old_id)->modify(array('language_iso' => $iso, 'dir' => @$tmp['dir'], 'name' => @$tmp['native'], 'flag' => ':NULL'));
			return;
		}
		if (!empty($target) && in_array((int) $target['id'], pjCompanyContext::companyLocaleIds($cid), true))
		{
			return; // the company has that language already
		}
		$new_id = !empty($target) ? (int) $target['id'] : $this->cSystemLocale($iso, $old_id);
		if ($new_id <= 0)
		{
			return;
		}
		self::cCopyTexts($old_id, $new_id, $cid);
		pjCompanyLocaleModel::factory()->where('company_id', $cid)->where('locale_id', $old_id)->limit(1)->modifyAll(array('locale_id' => $new_id));
		if ((int) $this->getLocaleId() === (int) $old_id)
		{
			$this->setLocale($new_id);
		}
		$this->cDropIfUnused($old_id);
	}

	/**
	 * Company label text: stored in the company's own field; the system text
	 * ("title") is not changed. A text equal to the system text with no own
	 * text yet is not stored.
	 */
	/**
	 * Label text saved by a company user: keep simple formatting tags only and drop
	 * scripts, event handlers and javascript: links (labels are printed unescaped on
	 * the admin and public pages, also to administrators working in that company).
	 */
	public static function cleanLabelHtml($content)
	{
		if (!is_string($content) || strpos($content, '<') === false)
		{
			return $content;
		}
		$content = preg_replace('#<(script|style|iframe|object|embed|svg|math)\b[^>]*>.*?</\1\s*>#is', '', $content);
		$content = strip_tags($content, '<br><b><strong><i><em><u><p><a><span><ul><ol><li><small><sup><sub>');
		$content = preg_replace('#\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $content);
		$content = preg_replace('#\s+(style|srcdoc|formaction)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $content);
		$content = preg_replace('#(href|src)\s*=\s*(["\']?)\s*(javascript|vbscript|data)\s*:#i', '$1=$2#', $content);
		return $content;
	}

	private function cSaveLabelOverride($field_id, $locale_id, $label_field, $content, $base)
	{
		if ((int) $this->getRoleId() !== 1)
		{
			$content = self::cleanLabelHtml($content);
		}
		$m = pjMultiLangModel::factory();
		$own = $m->where('t1.model', 'pjBaseField')->where('t1.foreign_id', (int) $field_id)->where('t1.locale', (int) $locale_id)->where('t1.field', $label_field)->limit(1)->findAll()->getDataIndex(0);
		if (!empty($own))
		{
			if ($own['content'] !== $content)
			{
				$m->reset()->set('id', $own['id'])->modify(array('content' => $content));
			}
			return;
		}
		if ((string) $content === (string) $base)
		{
			return;
		}
		$m->reset()->setAttributes(array('foreign_id' => (int) $field_id, 'model' => 'pjBaseField', 'locale' => (int) $locale_id, 'field' => $label_field, 'content' => $content))->insert();
	}

	protected static function updateFieldsIndex()
	{
		return pjBaseOptionModel::factory()
			->where('`key`', 'o_fields_index')
			->limit(1)
			->modifyAll(array('value' => md5(uniqid(rand(), true))))
			->getAffectedRows();
	}
		
	public function pjActionIsFlagReady()
	{
		if (!isset($this->option_arr['o_multi_lang']) || (int) $this->option_arr['o_multi_lang'] === 0)
		{
			return FALSE;
		}
		
		$cnt = pjLocaleModel::factory()
			->join('pjBaseLocaleLanguage', 't2.iso=t1.language_iso', 'left')
			->where('t2.file IS NOT NULL')
			->orderBy('t1.sort ASC')
			->findCount()
			->getData();
		
		return ($cnt > 1);
	} 
	
	public function pjActionLabels()
	{
	    $this->checkLogin();
	    
	    if (!pjAuth::factory('pjBaseLocale', 'pjActionLabels')->hasAccess())
        {
            $this->sendForbidden();
            return;
        }
	    
	    if (self::isPost())
	    {
	        if ($this->_post->check('lang_show_id'))
	        {
	        	if (pjAuth::factory('pjBaseLocale', 'pjActionLabels')->hasAccess())
                {
                    $this->session->setData('lang_show_id', $this->_post->check('show_id') ? 1 : 0);
	                self::updateFieldsIndex();
                }
	        }
	        
	        pjUtil::redirect($_SERVER['PHP_SELF'] . '?controller=pjBaseLocale&action=pjActionLabels');
	    }
	    
	    if (self::isGet())
	    {
	        $this->set('has_access_show_ids', pjAuth::factory('pjBaseLocale', 'pjActionLabels')->hasAccess());
	        $this->set('is_ids_shown', self::isIDsShown());
	        $this->appendJs('jquery.datagrid.js', $this->getConst('PLUGIN_JS_PATH'));
	        $this->appendJs('pjBaseLocale.js', $this->getConst('PLUGIN_JS_PATH'));
	    }
	}
	
	public function pjActionIndex()
	{
		$this->checkLogin();

		if (!pjAuth::factory()->hasAccess())
        {
        	if (pjAuth::factory('pjBaseLocale', 'pjActionLabels')->hasAccess())
            {
                pjUtil::redirect(PJ_INSTALL_URL . "index.php?controller=pjBaseLocale&action=pjActionLabels");
            } elseif (pjAuth::factory('pjBaseLocale', 'pjActionImportExport')->hasAccess()) {
                pjUtil::redirect(PJ_INSTALL_URL . "index.php?controller=pjBaseLocale&action=pjActionImportExport");
            } else {
                $this->sendForbidden();
                return;
            }
        }
		
		if (isset($this->option_arr['o_multi_lang']) && (int) $this->option_arr['o_multi_lang'] === 1)
		{
			$arr = pjLocaleLanguageModel::factory()->where('t1.file IS NOT NULL')->orderBy('t1.title ASC')->findAll()->getData();
			
			foreach ($arr as &$item)
			{
				if (!empty($item['region']))
				{
					$item['title'] = sprintf('%s (%s)', $item['title'], $item['region']);
				}
			}
			
			$this->set('language_arr', $arr);
			
			$this->appendJs('jquery-ui.min.js', PJ_THIRD_PARTY_PATH . 'jquery_ui/');
			$this->appendJs('jquery.datagrid.js', $this->getConst('PLUGIN_JS_PATH'));
			$this->appendJs('pjBaseLocale.js', $this->getConst('PLUGIN_JS_PATH'));
		} else {
			$this->set('status', 3);
			return;
		}
	}
	
	/** Labels are saved for the active company (shared texts stay the default). */
	private function labelFieldName()
	{
		$cid = $this->getLabelsCompanyId();
		return $cid > 0 && class_exists('pjCompanyContext') ? pjCompanyContext::labelField($cid) : 'title';
	}

	private function toCompanyLabelData($arr)
	{
		$name = $this->labelFieldName();
		$out = array();
		foreach ((array) $arr as $locale_id => $fields)
		{
			foreach ((array) $fields as $field => $content)
			{
				$out[$locale_id][$field === 'title' ? $name : $field] = (int) $this->getRoleId() !== 1 ? self::cleanLabelHtml($content) : $content;
			}
		}
		return $out;
	}

	public function pjActionSaveFields()
	{
		$this->checkLogin();
		
		if (!$this->isAdmin())
		{
			$this->sendForbidden();
			return;
		}

		$i18n = $this->_post->toI18n('i18n');
		if (count($i18n) > 0)
		{
			$pjBaseFieldModel = pjBaseFieldModel::factory();
			$MultiLangModel = pjMultiLangModel::factory();
			$MultiLangModel->begin();
			foreach ($i18n as $locale_id => $arr)
			{
				foreach ($arr as $foreign_id => $locale_arr)
				{
					$data = array();
					$data[$locale_id] = array();
					foreach ($locale_arr as $name => $content)
					{
						$data[$locale_id][$name] = $content;
					}
					$data = $this->toCompanyLabelData($data);
					$fids = $MultiLangModel->updateMultiLang($data, $foreign_id, 'pjBaseField');
					if (!empty($fids))
					{
						$pjBaseFieldModel->reset()->whereIn('id', $fids)->limit(count($fids))->modifyAll(array('modified' => ':NOW()'));
					}
				}
			}
			$MultiLangModel->commit();
			$this->updateFieldsIndex();
		}
		pjUtil::redirect(sprintf("%sindex.php?controller=pjBaseLocale&action=%s&err=PAL01&tab=1&q=%s&locale=%u&page=%u", PJ_INSTALL_URL, $this->_post->toString('next_action'), urlencode($this->_post->toString('q')), $this->_post->toInt('locale'), $this->_post->toInt('page')));
		exit;
	}
	
	public function pjActionDeleteLocale()
	{
		$this->setAjax(true);
	
		if ($this->isXHR())
		{
		    if (!pjAuth::factory('pjBaseLocale', 'pjActionIndex')->hasAccess())
		    {
		        self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'Access denied.'));
		    }
		    if ($this->_get->toInt('id') <= 0)
		    {
		        self::jsonResponse(array('status' => 'ERR', 'code' => 101, 'text' => 'Missing, empty or invalid parameters.'));
		    }
		    $id = $this->_get->toInt('id');
		    
			$cid = $this->cLocaleCompany();
			if ($cid > 0)
			{
				// Remove the language from this company only. The system language and
				// its texts are deleted when no company uses it any more.
				$own = pjCompanyContext::companyLocaleIds($cid);
				if (!in_array($id, $own, true))
				{
					self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'Locale not found.'));
				}
				if (count($own) <= 1)
				{
					self::jsonResponse(array('status' => 'ERR', 'code' => 102, 'text' => 'The last language can not be deleted.'));
				}
				pjCompanyLocaleModel::factory()->where('company_id', $cid)->where('locale_id', $id)->limit(1)->eraseAll();
				self::cResort($cid);
				self::cCheckDefault($cid);
				if ((int) $this->getLocaleId() === $id)
				{
					$this->setLocale(pjCompanyContext::companyDefaultLocale($cid));
				}
				$this->cDropIfUnused($id);
				$this->updateFieldsIndex();
				self::jsonResponse(array('status' => 'OK', 'code' => 200, 'text' => 'Locale has been deleted.'));
			}
			
			$pjLocaleModel = pjLocaleModel::factory();
			$arr = $pjLocaleModel->find($id)->getData();
			if (empty($arr))
			{
				self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'Locale not found.'));
			}
			
			if ($pjLocaleModel->reset()->set('id', $id)->erase()->getAffectedRows() != 1)
			{
				self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'Locale has not been deleted.'));
			}
			
			self::removeFlagFile($arr);
			
			# Re-order
			$tmp = $pjLocaleModel
				->reset()
				->select('t1.id, t1.sort, @rownum := @rownum + 1 AS `row_number`')
				->join('CROSS JOIN (SELECT @rownum := 0) AS `r`')
				->orderBy('t1.sort ASC')
				->findAll()
				->getData();
			
			foreach ($tmp as $item)
			{
				if ($item['sort'] != $item['row_number'])
				{
					$pjLocaleModel->reset()->set('id', $item['id'])->modify(array('sort' => $item['row_number']));
				}
			}
			
			pjMultiLangModel::factory()->where('locale', $id)->eraseAll();
			$this->updateFieldsIndex();
			
			self::checkDefault();
			
			self::jsonResponse(array('status' => 'OK', 'code' => 200, 'text' => 'Locale has been deleted.'));
		}
		exit;
	}
	
	public function pjActionGetLocale()
	{
		// Same permission as the page this list/action belongs to (the plugin does not check it otherwise).
		if ($this->isLoged() && !pjAuth::factory('pjBaseLocale')->hasAccess())
		{
			if ($this->isXHR()) { self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'Access denied.')); }
			$this->sendForbidden();
			return;
		}
		$this->setAjax(true);
	
		if ($this->isXHR())
		{
			$pjLocaleModel = pjLocaleModel::factory();
			
			if ($this->_get->toString('q'))
			{
			    $q = $this->_get->toString('q');
				$q = str_replace(array('%', '_'), array('\%', '\_'), trim($pjLocaleModel->escapeStr($q)));
				$pjLocaleModel->where(sprintf("(t1.name LIKE '%1\$s' OR t1.language_iso LIKE '%1\$s')", "%$q%"));
			}
			
			$column = 't1.sort';
			$direction = 'ASC';
			// sort only by the grid's own columns
			$sortable = array('language_iso' => 't1.language_iso', 'name' => 't1.name', 'file' => '`file`', 'dir' => 't1.dir', 'is_default' => 't1.is_default', 'sort' => 't1.sort', 't1.sort' => 't1.sort');
			if ($this->_get->toString('direction') && $this->_get->toString('column') && isset($sortable[$this->_get->toString('column')]) && in_array(strtoupper($this->_get->toString('direction')), array('ASC', 'DESC')))
			{
			    $column = $sortable[$this->_get->toString('column')];
				$direction = strtoupper($this->_get->toString('direction'));
			}

			$total = $pjLocaleModel->findCount()->getData();
			$rowCount = 100;
			$pages = ceil($total / $rowCount);
			$page = $this->_get->toInt('page') > 0 ? $this->_get->toInt('page') : 1;
			$offset = ((int) $page - 1) * $rowCount;
			if ($page > $pages)
			{
				$page = $pages;
			}

			$data = $pjLocaleModel
				->select(sprintf("t1.*, t2.title, CONCAT('%spj/img/flags/', t2.file) AS `file`", PJ_FRAMEWORK_LIBS_PATH))
				->join('pjBaseLocaleLanguage', 't2.iso=t1.language_iso', 'left')
				->orderBy("$column $direction")->findAll()->getData();
						
			self::jsonResponse(compact('data', 'total', 'pages', 'page', 'rowCount', 'column', 'direction'));
		}
		exit;
	}
	
	public function pjActionGetLabels()
	{
		// Same permission as the page this list/action belongs to (the plugin does not check it otherwise).
		if ($this->isLoged() && !pjAuth::factory('pjBaseLocale')->hasAccess())
		{
			if ($this->isXHR()) { self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'Access denied.')); }
			$this->sendForbidden();
			return;
		}
		$this->setAjax(true);
		
		if ($this->isXHR())
		{
			$locale = $this->_get->toInt('locale_id');
			
			$default_locale = pjLocaleModel::factory()
				->select('t1.id')
				->where('t1.is_default', 1)
				->limit(1)
				->findAll()
				->getDataIndex(0);
			
			$default_locale = !empty($default_locale) ? $default_locale['id'] : $locale;
			
			$pjBaseFieldModel = pjBaseFieldModel::factory();
			
			$pjBaseFieldModel
				->join('pjBaseMultiLang', sprintf("t2.model='pjBaseField' AND t2.foreign_id=t1.id AND t2.locale='%u' AND t2.field='title'", $locale), 'left outer')
				->join('pjBaseMultiLang', sprintf("t3.model='pjBaseField' AND t3.foreign_id=t1.id AND t3.locale='%u' AND t3.field='title'", $default_locale), 'left outer')
				->join('pjBaseMultiLang', sprintf("t4.model='pjBaseField' AND t4.foreign_id=t1.id AND t4.locale='%u' AND t4.field='%s'", $locale, $this->labelFieldName()), 'left outer');
				
			if ($this->_get->toString('q'))
			{
			    $q = $this->_get->toString('q');
				$q = str_replace(array('%', '_'), array('\%', '\_'), trim($q));
				$q = $pjBaseFieldModel->escapeStr($q);
				$pjBaseFieldModel->where(sprintf("(t1.key LIKE '%1\$s' OR t2.content LIKE '%1\$s' OR t4.content LIKE '%1\$s' OR CONCAT(':', t1.id, ':') LIKE '%1\$s')", "%$q%"));
			}
			
			$column = 't1.id';
			$direction = 'ASC';
			// sort only by the grid's own columns
			$sortable = array('t1.id' => 't1.id', 'id' => 't1.id', 'did' => 't1.id', 'default_language' => '`default_language`', 'content' => '`content`');
			if ($this->_get->toString('direction') && $this->_get->toString('column') && isset($sortable[$this->_get->toString('column')]) && in_array(strtoupper($this->_get->toString('direction')), array('ASC', 'DESC')))
			{
			    $column = $sortable[$this->_get->toString('column')];
				$direction = strtoupper($this->_get->toString('direction'));
			}
		
			$total = $pjBaseFieldModel->findCount()->getData();
			$rowCount = $this->_get->toInt('rowCount') > 0 ? $this->_get->toInt('rowCount') : 10;
			$pages = ceil($total / $rowCount);
			$page = $this->_get->toInt('page') > 0 ? $this->_get->toInt('page') : 1;
			$offset = ((int) $page - 1) * $rowCount;
			if ($page > $pages)
			{
				$page = $pages;
			}
			
			if (self::isIDsShown())
			{
				$fields = "t1.id, t1.`type` AS `field_type`, t1.source, t3.content AS `default_language`, t2.locale, COALESCE(t4.content, t2.content) AS `content`, IF(CHAR_LENGTH(COALESCE(t4.content, t2.content))>70,1,0) AS `expand`, CONCAT(':', t1.id, ':') AS `did`";
			} else {
				$fields = "t1.id, t1.`type` AS `field_type`, t1.source, t3.content AS `default_language`, t2.locale, COALESCE(t4.content, t2.content) AS `content`, IF(CHAR_LENGTH(COALESCE(t4.content, t2.content))>70,1,0) AS `expand`";
			}
			
			$data = $pjBaseFieldModel
				->select($fields)
				->orderBy("$column $direction")
				->limit($rowCount, $offset)
				->findAll()
				->getData();
			foreach($data as $k => $v)
			{
			    $field_type_arr = array();
			    $field_type_arr[] = __('plugin_base_locale_type_' . $v['source'], true);
			    $field_type_arr[] = __('plugin_base_locale_type_' . $v['field_type'], true);
			    $v['field_type'] = join(" / ", $field_type_arr);
			    $data[$k] = $v;
			    
			}
			self::jsonResponse(compact('data', 'total', 'pages', 'page', 'rowCount', 'column', 'direction', 'locale'));
		}
		exit;
	}
	
	public function pjActionSaveLabel()
	{
		// Same permission as the page this list/action belongs to (the plugin does not check it otherwise).
		if ($this->isLoged() && !pjAuth::factory('pjBaseLocale')->hasAccess())
		{
			if ($this->isXHR()) { self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'Access denied.')); }
			$this->sendForbidden();
			return;
		}
		$this->setAjax(true);
		
		if ($this->isXHR())
		{
			if (!self::isPost())
			{
				self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'HTTP method not allowed.'));
			}
			if (!($this->_post->check('i18n') && $this->_post->check('foreign_id') && $this->_post->toInt('foreign_id') > 0))
			{
			    self::jsonResponse(array('status' => 'ERR', 'code' => 101, 'text' => 'Missing, empty or invalid parameters.'));
			}
			
			$fids = pjMultiLangModel::factory()->updateMultiLang($this->toCompanyLabelData($this->_post->toI18n('i18n')), $this->_post->toInt('foreign_id'), 'pjBaseField');
			$this->updateFieldsIndex();
			
			self::jsonResponse(array('status' => 'OK', 'code' => 200, 'text' => 'Label has been saved.', 'f' => $fids));
		}
		exit;
	}
	
	/** Delete an uploaded flag image - only from the upload folder, never any other file. */
	private static function removeFlagFile($arr)
	{
		if (empty($arr['flag']) || !is_file($arr['flag']))
		{
			return;
		}
		$file = realpath($arr['flag']);
		$base = realpath(PJ_INSTALL_PATH . (defined('PJ_UPLOAD_PATH') ? PJ_UPLOAD_PATH : 'app/web/upload/'));
		if ($file !== false && $base !== false && strpos($file, $base . DIRECTORY_SEPARATOR) === 0)
		{
			@unlink($file);
		}
	}

	public function pjActionSaveLocale()
	{
		$this->setAjax(true);
	
		if ($this->isXHR())
		{
			if (!self::isPost())
			{
				self::jsonResponse(array('status' => 'ERR', 'code' => 102, 'text' => 'HTTP method not allowed.'));
			}
			if (!pjAuth::factory('pjBaseLocale', 'pjActionIndex')->hasAccess())
            {
                self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'Access denied.'));
            }
			
			if ($this->_get->check('id') && $this->_post->check('column') && $this->_post->check('value')
			    && $this->_post->toString('column')
				&& $this->_get->toInt('id') > 0)
			{
			    // the grid edits only these columns (default / order have their own actions)
			    if (!in_array($this->_post->toString('column'), array('language_iso', 'name', 'dir'), true)
			        || ($this->_post->toString('column') === 'dir' && !in_array($this->_post->toString('value'), array('ltr', 'rtl'), true)))
			    {
			        self::jsonResponse(array('status' => 'ERR', 'code' => 104, 'text' => 'Invalid column.'));
			    }
			    $cid = $this->cLocaleCompany();
			    // a language row shared with other companies is renamed only by an administrator
			    if ($cid > 0 && $this->_post->toString('column') !== 'language_iso' && (int) $this->getRoleId() !== 1
			        && pjCompanyContext::localeUsage($this->_get->toInt('id')) > 1)
			    {
			        self::jsonResponse(array('status' => 'ERR', 'code' => 105, 'text' => 'This language is shared with other companies.'));
			    }
			    if ($cid > 0 && !in_array($this->_get->toInt('id'), pjCompanyContext::companyLocaleIds($cid), true))
			    {
			        self::jsonResponse(array('status' => 'ERR', 'code' => 103, 'text' => 'Locale not found.'));
			    }
			    if ($cid > 0 && $this->_post->toString('column') == 'language_iso')
			    {
			        $this->cChangeLocale($cid, $this->_get->toInt('id'), $this->_post->toString('value'));
			        $this->updateFieldsIndex();
			        self::jsonResponse(array('status' => 'OK', 'code' => 201, 'text' => 'Locale has been updated.'));
			    }
			    $data = array($this->_post->toString('column') => $this->_post->toString('value'));
			    if ($this->_post->toString('column') == 'language_iso')
				{
				    $tmp = pjLocaleLanguageModel::factory()->where('t1.iso', $this->_post->toString('value'))->limit(1)->findAll()->getDataIndex(0);
				    if (empty($tmp))
				    {
				        self::jsonResponse(array('status' => 'ERR', 'code' => 104, 'text' => 'Unknown language.'));
				    }
					
					$data['dir'] = $tmp['dir'];
					$data['name'] = $tmp['native'];
					$data['flag'] = ':NULL';
				}
					
				pjLocaleModel::factory()->set('id', $this->_get->toInt('id'))->modify($data);
				self::jsonResponse(array('status' => 'OK', 'code' => 201, 'text' => 'Locale has been updated.'));
			}
			
			if ($this->cLocaleCompany() > 0)
			{
				$result = $this->cAddLocale($this->cLocaleCompany());
				if ($result['status'] === 'OK')
				{
					$this->updateFieldsIndex();
				}
				self::jsonResponse($result);
			}
			
			$lang = pjLocaleLanguageModel::factory()
				->where(sprintf("t1.iso NOT IN (SELECT `language_iso` FROM `%s`)", pjLocaleModel::factory()->getTable()))
				->where('t1.file IS NOT NULL')
				->orderBy('t1.title ASC')
				->limit(1)
				->findAll()
				->getDataPair(null, 'iso');
			
			$result = $this->addLocale(@$lang[0]);
			if ($result['status'] === 'OK')
			{
				$this->updateFieldsIndex();
			}
			self::jsonResponse($result);
		}
		exit;
	}
	
	public function pjActionAddLocale()
	{
		// Same permission as the page this list/action belongs to (the plugin does not check it otherwise).
		if ($this->isLoged() && !pjAuth::factory('pjBaseLocale')->hasAccess())
		{
			if ($this->isXHR()) { self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'Access denied.')); }
			$this->sendForbidden();
			return;
		}
		$params = $this->getParams();
		
		return $this->addLocale($params['iso'], @$params['locale']);
	}
	
	public function pjActionSaveDefault()
	{
		// Same permission as the page this list/action belongs to (the plugin does not check it otherwise).
		if ($this->isLoged() && !pjAuth::factory('pjBaseLocale')->hasAccess())
		{
			if ($this->isXHR()) { self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'Access denied.')); }
			$this->sendForbidden();
			return;
		}
		$this->setAjax(true);
	
		if ($this->isXHR())
		{
			if (!self::isPost())
			{
				self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'HTTP method not allowed.'));
			}
			
			if ($this->_get->toInt('id') <= 0)
			{
				self::jsonResponse(array('status' => 'ERR', 'code' => 101, 'text' => 'Missing, empty or invalid parameters.'));
			}
			$id = $this->_get->toInt('id');
			$cid = $this->cLocaleCompany();
			if ($cid > 0)
			{
				// default language of this company only
				if (!in_array($id, pjCompanyContext::companyLocaleIds($cid), true))
				{
					self::jsonResponse(array('status' => 'ERR', 'code' => 102, 'text' => 'Locale not found.'));
				}
				pjCompanyLocaleModel::factory()->where('company_id', $cid)->modifyAll(array('is_default' => 0))
					->reset()->where('company_id', $cid)->where('locale_id', $id)->limit(1)->modifyAll(array('is_default' => 1));
				$this->setLocale($id);
				self::jsonResponse(array('status' => 'OK', 'code' => 200, 'text' => 'Default choise has been made.'));
			}
			pjLocaleModel::factory()
				->where(1,1)
				->modifyAll(array('is_default' => '0'))
				->reset()
				->set('id', $id)
				->modify(array('is_default' => 1));
				
			$this->setLocale($id);
			
			self::jsonResponse(array('status' => 'OK', 'code' => 200, 'text' => 'Default choise has been made.'));
		}
		exit;
	}
	
	public function pjActionSortLocale()
	{
		// Same permission as the page this list/action belongs to (the plugin does not check it otherwise).
		if ($this->isLoged() && !pjAuth::factory('pjBaseLocale')->hasAccess())
		{
			if ($this->isXHR()) { self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'Access denied.')); }
			$this->sendForbidden();
			return;
		}
		$this->setAjax(true);
	
		if ($this->isXHR())
		{
		    $sort_arr = $this->_post->toArray('sort');
			$cid = $this->cLocaleCompany();
			if ($cid > 0)
			{
				// order of this company's languages only
				$own = pjCompanyContext::companyLocaleIds($cid);
				$n = 0;
				foreach ($sort_arr as $lid)
				{
					if (in_array((int) $lid, $own, true))
					{
						pjCompanyLocaleModel::factory()->where('company_id', $cid)->where('locale_id', (int) $lid)->limit(1)->modifyAll(array('sort' => ++$n));
					}
				}
				exit;
			}
			$LocaleModel = new pjLocaleModel();
			$arr = $LocaleModel->whereIn('id', $sort_arr)->orderBy("t1.sort ASC")->findAll()->getDataPair('id', 'sort');
			$fliped = array_flip($sort_arr);
			$combined = array_combine(array_keys($fliped), $arr);
			$LocaleModel->begin();
			foreach ($combined as $id => $sort)
			{
				$LocaleModel->setAttributes(compact('id'))->modify(compact('sort'));
			}
			$LocaleModel->commit();
		}
		exit;
	}
	
	public function pjActionGetIso()
	{
		$this->setAjax(true);
		
		if ($this->isXHR())
		{
			if (!self::isGet())
			{
				self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'HTTP method not allowed.'));
			}
			if ($this->_get->toInt('id') <= 0)
			{
				self::jsonResponse(array('status' => 'ERR', 'code' => 101, 'text' => 'Missing, empty or invalid parameters.'));
			}
			$id = $this->_get->toInt('id');
			$arr = pjLocaleModel::factory()->find($id)->getData();
			
			if ($arr)
			{
				self::jsonResponse(array('status' => 'OK', 'code' => 200, 'text' => 'Success', 'language_iso' => $arr['language_iso']));
			}
			
			self::jsonResponse(array('status' => 'ERR', 'code' => 103, 'text' => 'Language not found.'));
		}
	}
	
	
	public function pjActionImportExport()
	{
	    $this->checkLogin();

	    $pjAuth = pjAuth::factory();
	    if (!pjAuth::factory('pjBaseLocale', 'pjActionImportExport')->hasAccess())
	    {
	        $this->sendForbidden();
	        return;
	    }

	    $this->set('has_access_import', pjAuth::factory('pjBaseLocale', 'pjActionImportExport')->hasAccess(''));
	    $this->set('has_access_export', pjAuth::factory('pjBaseLocale', 'pjActionImportExport')->hasAccess(''));

	    $this->appendCss('jasny-bootstrap.min.css', PJ_THIRD_PARTY_PATH . 'jasny/');
	    $this->appendJs('jasny-bootstrap.min.js',  PJ_THIRD_PARTY_PATH . 'jasny/');
	    $this->appendJs('pjBaseLocale.js', $this->getConst('PLUGIN_JS_PATH'));
	}
	
	public function pjActionImportConfirm()
	{
	    $this->checkLogin();
	    
	    if (!pjAuth::factory('pjBaseLocale', 'pjActionImportExport')->hasAccess())
	    {
	        $this->sendForbidden();
	        return;
	    }
	    
	    $pjLocaleModel = pjLocaleModel::factory()
    	    ->select('t1.*, t2.title, t2.region')
    	    ->join('pjBaseLocaleLanguage', 't2.iso=t1.language_iso');
	    if (!isset($this->option_arr['o_multi_lang']) || (int) $this->option_arr['o_multi_lang'] === 0)
	    {
	        $pjLocaleModel->where('t1.is_default', 1);
	    }
	    $locale_arr = $pjLocaleModel->orderBy('t1.sort ASC')->findAll()->getDataPair('id');
	    
	    $columns = count($locale_arr) + 2;
	    
	    if ($this->_post->toInt('import'))
	    {
	        if (isset($_FILES['file']) && $this->_post->check('separator'))
	        {
	            $pjUpload = new pjUpload();
	            $pjUpload->setAllowedExt(array('csv', 'txt'));
	            $pjUpload->setAllowedTypes(array(
	                'text/plain',
	                'text/x-comma-separated-values',
	                'text/comma-separated-values',
	                'application/x-csv',
	                'application/csv',
	                'text/x-csv',
	                'text/csv',
	                'application/vnd.ms-excel',
	                'application/octet-stream',
	            ));
	            
	            if ($pjUpload->load($_FILES['file']))
	            {
	                if (($handle = fopen($pjUpload->getFile('tmp_name'), "rb")) !== FALSE)
	                {
	                    $separators = array(
	                        'comma' => ",",
	                        'semicolon' => ";",
	                        'tab' => "\t"
	                    );
	                    $separator = $separators[$this->_post->toString('separator')];
	                    
	                    $field_arr = pjBaseFieldModel::factory()->findAll()->getDataPair('id', 'key');
	                    
	                    $time = time();
	                    
	                    if(!$this->session->has($this->pjBaseLocaleError))
	                    {
	                        $this->session->setData($this->pjBaseLocaleError, array());
	                    }
	                    
	                    $i = 1;
	                    $prev_cnt = 0;
	                    $header = array();
	                    while (($data = fgetcsv($handle, 0, $separator)) !== FALSE)
	                    {
	                        if (!empty($data))
	                        {
	                            $nl = preg_grep('/\r\n|\n/', $data);
	                            if (!empty($nl))
	                            {
	                                $this->session->setData($this->pjBaseLocaleError, array($time => sprintf(__('plugin_base_locale_error_line', true), $i)));
	                                $err = 'PAL14&tm=' . $time;
	                                break;
	                            }
	                            
	                            $cnt = count($data);
	                            if ($cnt <= 2)
	                            {
	                                $this->session->setData($this->pjBaseLocaleError, array($time => sprintf(__('plugin_base_locale_error_line', true), $i)));
	                                $err = 'PAL15&tm=' . $time;
	                                break;
	                            }
	                            if ($prev_cnt > 0 && $cnt != $prev_cnt)
	                            {
	                                $this->session->setData($this->pjBaseLocaleError, array($time => sprintf(__('plugin_base_locale_error_line', true), $i)));
	                                $err = 'PAL16&tm=' . $time;
	                                break;
	                            }
	                            
	                            if ($i > 1 && isset($id, $key) && $id !== FALSE && $key !== FALSE)
	                            {
	                                if (!preg_match('/^\d+$/', $data[$id]) || !preg_match('/^[\w\-]+$/', $data[$key]))
	                                {
	                                    $this->session->setData($this->pjBaseLocaleError, array($time => sprintf(__('plugin_base_locale_error_line', true), $i)));
	                                    $err = 'PAL19&tm=' . $time;
	                                    break;
	                                }
	                                if (!isset($field_arr[$data[$id]]))
	                                {
	                                    continue;
	                                }
	                                if (isset($field_arr[$data[$id]]) && $data[$key] != $field_arr[$data[$id]])
	                                {
	                                    continue;
	                                }
	                            } elseif ($i == 1) {
	                                $header = $data;
	                                $id = array_search('id', $data);
	                                $key = array_search('key', $data);
	                                if ($id === FALSE || $key === FALSE)
	                                {
	                                    $this->session->setData($this->pjBaseLocaleError, array($time => sprintf(__('plugin_base_locale_error_line', true), $i)));
	                                    $err = 'PAL18&tm=' . $time;
	                                    break;
	                                }
	                            }
	                            
	                            $prev_cnt = $cnt;
	                            $i += 1;
	                        } else {
	                            $this->session->setData($this->pjBaseLocaleError, array($time => sprintf(__('plugin_base_locale_error_line', true), $i)));
	                            $err = 'PAL17&tm=' . $time;
	                            break;
	                        }
	                    }
	                    fclose($handle);
	                } else {
	                    $err = 'PAL13';
	                }
	            } else {
	                echo $pjUpload->getErrorCode();
	                echo $pjUpload->getError();
	                exit;
	                $err = 'PAL12';
	            }
	        } else {
	            $err = 'PAL11';
	        }
	        
	        if (!isset($err))
	        {
	            $locales = array();
	            foreach ($header as $k => $col)
	            {
	                if (in_array($k, array($id, $key)))
	                {
	                    continue;
	                }
	                list($locales[],) = explode('::', $col);
	            }
	            
	            $key = md5(uniqid(rand(), true));
	            $dest = PJ_UPLOAD_PATH . $key . ".csv";
	            if ($pjUpload->save($dest))
	            {
	                $this->session->setData($key, array(
	                    'name' => $dest,
	                    'separator' => $this->_post->toString('separator'),
	                    'locales' => $locales
	                ));
	                $err = 'PAL20&key=' . $key;
	            } else {
	                $err = 'PAL20';
	            }
	        }
	        pjUtil::redirect(PJ_INSTALL_URL . "index.php?controller=pjBaseLocale&action=pjActionImportConfirm&tab=1&err=".$err);
	    }
	    $this->set('locale_arr', $locale_arr);
	    if($this->_get->toString('tm'))
	    {
	        if($this->session->has($this->pjBaseLocaleError))
	        {
	            $locale_error = $this->session->getData($this->pjBaseLocaleError);
	            if(isset($locale_error[$this->_get->toString('tm')]))
	            {
	                $this->set('tm_text', $locale_error[$this->_get->toString('tm')]);
	            }
	        }
	    }
	}
	
	public function pjActionImport()
	{
	    $this->checkLogin();
	    
	    if (!pjAuth::factory('pjBaseLocale', 'pjActionImportExport')->hasAccess())
	    {
	        $this->sendForbidden();
	        return;
	    }
	    
	    $this->setAjax(true);
	    $this->setLayout('pjActionEmpty');
	    
	    $err = 'PAL02';
	    if ($this->_post->toInt('import'))
	    {
	        @set_time_limit(600); //10 min

            $post = array();
            $post['key'] = $this->_post->toString('key');
            $post['locale'] = $this->_post->toArray('locale');

	        if ($post['key'] && $post['locale'] && $this->session->has($post['key']))
	        {
	            $post_key_arr = $this->session->getData($post['key']);
	            if(isset($post_key_arr['name'], $post_key_arr['separator']) && !empty($post_key_arr['name']) && !empty($post_key_arr['separator']))
	            {
    	            if (($handle = fopen($post_key_arr['name'], "rb")) !== FALSE)
    	            {
    	                $pjMultiLangModel = pjMultiLangModel::factory();
    	                
    	                // Company: imported texts are saved for this company only (label override
    	                // field), the system texts of the other companies stay as they are.
    	                $label_field = $this->labelFieldName();
    	                $multi_lang_arr = $pjMultiLangModel
        	                ->select('t1.locale, t1.id AS `mid`, t1.content AS `base`, t2.id, t2.key')
        	                ->join('pjBaseField', 't2.id=t1.foreign_id', 'inner')
        	                ->where('t1.model', 'pjBaseField')
        	                ->where('t1.field', 'title')
        	                ->whereIn('t1.locale', $post['locale'])
        	                ->where('t1.source !=', 'data')
        	                ->findAll()
        	                ->getData();
    	                
    	                if (empty($multi_lang_arr))
    	                {
    	                    exit;
    	                }
    	                
    	                $import_arr = array();
    	                foreach ($multi_lang_arr as $k => $item)
    	                {
    	                    if (!isset($import_arr[$item['key']]))
    	                    {
    	                        $import_arr[$item['key']] = array(
    	                            'id' => $item['id'],
    	                            'key' => $item['key'],
    	                            'locales' => array()
    	                        );
    	                    }
    	                    $import_arr[$item['key']]['locales'][$item['locale']] = $item['mid'];
    	                    $import_arr[$item['key']]['base'][$item['locale']] = $item['base'];
    	                }
    	                
    	                if (empty($import_arr))
    	                {
    	                    exit;
    	                }
    	                
    	                $separators = array(
    	                    'comma' => ",",
    	                    'semicolon' => ";",
    	                    'tab' => "\t"
    	                );
    	                $separator = $separators[$post_key_arr['separator']];
    	                
    	                $pjMultiLangModel->reset()->begin();
    	                
    	                $i = 1;
    	                while (($data = fgetcsv($handle, 0, $separator)) !== FALSE)
    	                {
    	                    if (!empty($data))
    	                    {
    	                        if ($i > 1 && isset($id, $key, $locales)
    	                            && !empty($locales)
    	                            && $id !== FALSE
    	                            && $key !== FALSE
    	                            && isset($import_arr[$data[$key]]))
    	                        {
    	                            foreach ($import_arr[$data[$key]]['locales'] as $locale_id => $mid)
    	                            {
    	                                if (($index = array_search($locale_id, $locales)) !== FALSE
    	                                    && $data[$key] == $import_arr[$data[$key]]['key'])
    	                                {
    	                                    $content = str_replace(array('\n', '\t'), array("\r\n", "\t"), $data[$index]);
    	                                    if ($label_field !== 'title')
    	                                    {
    	                                        $this->cSaveLabelOverride($import_arr[$data[$key]]['id'], $locale_id, $label_field, $content, $import_arr[$data[$key]]['base'][$locale_id]);
    	                                        continue;
    	                                    }
    	                                    $pjMultiLangModel
    	                                    ->set('id', $mid)
    	                                    ->modify(array(
    	                                        'content' => $content
    	                                    ));
    	                                }
    	                            }
    	                        } elseif ($i == 1) {
    	                            $id = array_search('id', $data);
    	                            $key = array_search('key', $data);
    	                            if ($id !== FALSE && $key !== FALSE)
    	                            {
    	                                $locales = array();
    	                                foreach ($data as $k => $col)
    	                                {
    	                                    if (in_array($k, array($id, $key)))
    	                                    {
    	                                        continue;
    	                                    }
    	                                    list($loc,) = explode('::', $col);
    	                                    $locales[$k] = $loc;
    	                                }
    	                            }
    	                        }
    	                        $i += 1;
    	                    }
    	                }
    	                fclose($handle);
    	                @unlink($post_key_arr['name']);
    	                
    	                if ($i > 1)
    	                {
    	                    $pjMultiLangModel->commit();
    	                    $this->pjActionUpdateFieldsIndex();
    	                    $err = 'PAL03';
    	                } else {
    	                    $err = 'PAL04';
    	                }
    	            } else {
    	                $err = 'PAL05';
    	            }
	            }
	        }
	    }
	    pjUtil::redirect(PJ_INSTALL_URL . "index.php?controller=pjBaseLocale&action=pjActionImportExport&err=".$err);
	}
	
	public function pjActionExport()
	{
	    $this->checkLogin();
	    
	    if (!pjAuth::factory('pjBaseLocale', 'pjActionImportExport')->hasAccess())
	    {
	        $this->sendForbidden();
	        return;
	    }
	    
	    $this->setAjax(true);
	    $this->setLayout('pjActionEmpty');
	    
	    if ($this->_post->toInt('export') && $this->_post->toString('separator'))
	    {
	        @set_time_limit(600); //10 min
	        
	        $name = 'pjBaseLocale-'.time();

	        $pjMultiLangModel = pjMultiLangModel::factory();
	        
	        $pjLocaleModel = pjLocaleModel::factory()
    	        ->select('t1.*, t2.title, t2.region')
    	        ->join('pjBaseLocaleLanguage', 't2.iso=t1.language_iso');
	        if (!isset($this->option_arr['o_multi_lang']) || (int) $this->option_arr['o_multi_lang'] === 0)
	        {
	            $pjLocaleModel->where('t1.is_default', 1);
	        }
	        
	        $locale_arr = $pjLocaleModel->orderBy('t1.sort ASC')->findAll()->getDataPair('id');
	        
	        if (empty($locale_arr))
	        {
	            exit;
	        }
	        
	        // Company: export the texts as this company sees them (its own label texts first).
	        $label_field = $this->labelFieldName();
	        $multi_lang_arr = $pjMultiLangModel
    	        ->select('t1.locale, COALESCE(t3.content, t1.content) AS `content`, t2.id, t2.key')
    	        ->join('pjBaseField', 't2.id=t1.foreign_id', 'left outer')
    	        ->join('pjMultiLang', sprintf("t3.model='pjBaseField' AND t3.foreign_id=t1.foreign_id AND t3.locale=t1.locale AND t3.field='%s'", $pjMultiLangModel->escapeStr($label_field)), 'left outer')
    	        ->where('t1.model', 'pjBaseField')
    	        ->where('t1.field', 'title')
    	        ->whereIn('t1.locale', array_keys($locale_arr))
    	        ->where('t1.source !=', 'data')
    	        ->findAll()
    	        ->getData();
	        
	        if (empty($multi_lang_arr))
	        {
	            exit;
	        }
	        
	        $export_arr = array();
	        foreach ($multi_lang_arr as $k => $item)
	        {
	            if (!isset($export_arr[$item['id']]))
	            {
	                $export_arr[$item['id']] = array(
	                    'key' => $item['key'],
	                    'locales' => array()
	                );
	            }
	            $export_arr[$item['id']]['locales'][$item['locale']] = $item['content'];
	        }
	        
	        $csv = array();
	        
	        $separators = array(
	            'comma' => ",",
	            'semicolon' => ";",
	            'tab' => "\t"
	        );
	        $separator = $separators[$this->_post->toString('separator')];
	        
	        $header = array('id', 'key');
	        foreach ($locale_arr as $id => $data)
	        {
	            $title = $data['title'] . (!empty($data['region']) ? sprintf(' (%s)', $data['region']) : NULL);
	            $title = str_replace(array(',', ';'), ' ', $title);
	            $title = preg_replace('/\t/', ' ', $title);
	            $header[] = $id . '::' . $title;
	        }
	        $csv[] = join($separator, $header);
	        
	        foreach ($export_arr as $id => $data)
	        {
	            if(!empty($id))
	            {
	                $cells = array();
	                $cells[] = '"' . (int) $id . '"';
	                $cells[] = '"' . str_replace(array("\r\n", "\n", "\t", '"'), array('\n', '\n', '\t', '""'), $data['key']) . '"';
	                foreach ($locale_arr as $locale_id => $item)
	                {
	                    if (isset($data['locales'][$locale_id]))
	                    {
	                        $cells[] = '"' . str_replace(array("\r\n", "\n", "\t", '"'), array('\n', '\n', '\t', '""'), $data['locales'][$locale_id]) . '"';
	                    } else {
	                        $cells[] = '""';
	                    }
	                }
	                
	                $csv[] = "\n";
	                $csv[] = join($separator, $cells);
	            }
	        }
	        
	        $content = join("", $csv);
	        pjToolkit::download($content, $name.'.csv');
	    }
	    exit;
	}
}
?>