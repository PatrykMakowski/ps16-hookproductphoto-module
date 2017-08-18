<?php
/***
	@name: HookProductPhoto
	@author: Patryk Makowski
	@contact: kontakt@patrykmakowski.pl
	@compitality: PS 1.6, PHP 5.6+
	@version: 1.0

	Q:	How to use module?
	A:	Add hook to this module in your /THEME/product.pl
			{hook h='displayHookProductPhoto' product=$product link=$link}


***/
if(!defined('_PS_VERSION_'))
    exit;

class hookProductPhoto extends Module
{
	private $hpp_config;
	const DEFAULT_CONFIG = array(
		'image_size' => 'large_default'
	);

    public function __construct()
    {
        $this->name = 'hookproductphoto';
        $this->tab = 'front_office_features';
        $this->version = '1.0';
        $this->author = 'Patryk Makowski';
        $this->need_instance = 0;
		$this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.6.99.99');
        parent::__construct();
        $this->displayName = $this->l('Zdjęcie w parametrach');
        $this->description = $this->l('Dodaje hook do wybranego zdjęcia produktu');	
		$this->confirmUninstall = $this->l('Jesteś pewny? Może to usunać wszystkie wybrane zdjęcia!');

		if (!Configuration::get('HOOKPRODUCTPHOTO_CFG')) {
			$this->warning = $this->l('Brak konfiguacji modułu zdjęć w parametrach');
		}
		else 
		{
			$this->hpp_config = unserialize(base64_decode(Configuration::get('HOOKPRODUCTPHOTO_CFG')));
		}
    }

	public function install()
	{
		$this->_clearCache('*');

		if (!parent::install() OR
			!$this->createTable() OR         
			!$this->registerHook('actionProductUpdate') OR
			!$this->registerHook('displayAdminProductsExtra') OR
			!$this->registerHook('displayHookProductPhoto') OR
			!$this->registerHook('actionProductDelete') OR
			!Configuration::updateValue('HOOKPRODUCTPHOTO_CFG', base64_encode(serialize(self::DEFAULT_CONFIG)))
			)
			return false;

		return true;
	}

	public function uninstall()
	{
		if (!parent::uninstall())
			return false;

		return true;
	}

	private function createTable()
	{
		$sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'hookproductphoto` (';
		$sql .= '`id_hpp` INT(11) PRIMARY KEY AUTO_INCREMENT,';
		$sql .= '`id_product` INT(11) NULL DEFAULT NULL,';
		$sql .= '`id_image` INT(11) NULL DEFAULT NULL';
		$sql .= ')';
		
		if(!Db::getInstance()->execute($sql))
			return false;
		
		return true;
	}

	public function hookDisplayHookProductPhoto($params)
	{
		if(isset($params['id_product']) && is_numeric($params['id_product']))
			$id_product = (int)$params['id_product'];
		else if ($this->context->controller instanceof ProductController)
			$id_product = (int)Tools::getValue('id_product');
		else
			return false;
		
		$cache_id = 'hookproductphoto|'. $id_product;
		if (!$this->isCached('hook.tpl', $this->getCacheId($cache_id)))
		{			
			$img = $this->getProductPhotoForProduct($id_product);
			$this->context->smarty->assign(array_merge($params, array(
				'hpp_imageid' => $img,
				'id_product' => $id_product,
				'hpp_config' => $this->hpp_config,
			)));
		}
		return $this->display(__FILE__, 'hook.tpl', $this->getCacheId($cache_id));		
	}

	public function hookDisplayAdminProductsExtra($params)
	{
		if(Validate::isLoadedObject($product = new Product((int)Tools::getValue('id_product'))))
		{
			$images = $product->getImages((int)$this->context->language->id);
			if($images)
			{
				$id = $this->getProductPhotoForProduct((int)Tools::getValue('id_product'));			
				$this->context->smarty->assign(array(
					'images' => $product->getImages((int)$this->context->language->id),
					'id_product' => (int)Tools::getValue('id_product'),
					'current_hpp' => $id,
				));
				return $this->display(__FILE__, 'admin.tpl');
			}
			else
			{
				return $this->displayError($this->l('Musisz najpierw dodać zdjęcia produktu'));				
			}
		}
		else
		{
            return $this->displayError($this->l('Musisz zapisać ten produkt przed dodaniem nowego opisu'));
		}
	}
	
	public function hookActionProductUpdate($params)
	{
        $id_product = (int)Tools::getValue('id_product');
		$new_hpp = Tools::getValue('hpp_image');
		$current_hpp = $this->getProductPhotoForProduct($id_product);
		
		//Usunięcie 
		if($current_hpp && $new_hpp == 'null')
		{
			Db::getInstance()->delete('hookproductphoto', "id_product='{$id_product}'");
		}
		
		//Dodanie
		if(!$current_hpp && $new_hpp != 'null' && $new_hpp)
		{
			Db::getInstance()->insert('hookproductphoto', array('id_product' => pSQL($id_product), 'id_image' => pSQL($new_hpp)));
		}
		
		//Zmiana
		if($current_hpp && $new_hpp != 'null' && $new_hpp)
		{
			Db::getInstance()->update('hookproductphoto', array('id_image' => pSQL($new_hpp)), "id_product='{$id_product}'");	
		}
	}
	
	public function hookActionProductDelete($params)
	{
		if(isset($params['id_product']) && is_numeric($params['id_product']))
		{
			Db::getInstance()->delete('hookproductphoto', "id_product = '{$params['id_product']}'");
		}
	}

	private function getProductPhotoForProduct($id_product)
	{
		$id = Db::getInstance()->getValue('SELECT `id_image` FROM '._DB_PREFIX_.'hookproductphoto WHERE id_product = '. (int)$id_product);
		if($id)
			return $id;
		else
			return null;
	}
	
	public function getContent()
	{
		$output = null;
		if(Tools::isSubmit('submit'.$this->name))
		{
			$image_size = Tools::getValue('image_size');
			if(!$image_size || empty($image_size))
				$output .= $this->displayError($this->l('Nieprawidłowa wartość w "image_size"'));
			else 
			{
				$this->hpp_config['image_size'] = $image_size;
				$output .= $this->displayConfirmation($this->l('Zapisano parametr "image_size"'));
			}

			Configuration::updateValue('HOOKPRODUCTPHOTO_CFG', base64_encode(serialize($this->hpp_config)));
		}		
		return $output . $this->displayForm();
	}

	public function displayForm()
	{
		$default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

		$fields_form[0]['form'] = array(
			'legend' => array(
				'title' => $this->l('Konfiguracja'),
			),
			'input' => array(
				array(
					'type' => 'text',
					'label' => $this->l('Rozmiar zdjęć w parametrach'),
					'name' => 'image_size',
					'size' => 20,
					'required' => true
				),
			),
			'submit' => array(
				'title' => $this->l('Zapisz'),
				'class' => 'btn btn-default pull-right'
			)
		);

		$helper = new HelperForm();

		$helper->module = $this;
		$helper->name_controller = $this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

		$helper->default_form_language = $default_lang;
		$helper->allow_employee_form_lang = $default_lang;

		$helper->title = $this->displayName;
		$helper->show_toolbar = true;
		$helper->toolbar_scroll = true;
		$helper->submit_action = 'submit'.$this->name;
		$helper->toolbar_btn = array(
			'save' =>
			array(
				'desc' => $this->l('Zapisz'),
				'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
				'&token='.Tools::getAdminTokenLite('AdminModules'),
			),
			'back' => array(
				'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
				'desc' => $this->l('Wróć')
			)
		);

		$helper->fields_value['image_size'] = isset($this->hpp_config['image_size']) ? $this->hpp_config['image_size'] : self::DEFAULT_CONFIG['image_size'];
		return $helper->generateForm($fields_form);
	}
}