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

	private function getProductPhotoForProduct($id_product)
	{
		$id = Db::getInstance()->getValue('SELECT `id_image` FROM '._DB_PREFIX_.'hookproductphoto WHERE id_product = '. (int)$id_product);
		if($id)
			return $id;
		else
			return null;
	}
}