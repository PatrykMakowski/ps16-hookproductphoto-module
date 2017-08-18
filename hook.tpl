{*
	Default vars:
		$hpp_imageid (int)
		$hpp_config (array)
		$id_product (int)
		
	Imported from product.tpl:
		$link (object)
		$product (object)
*}
{if $hpp_imageid}
	<img class="img-responsive" src="{$link->getImageLink($product->link_rewrite, $hpp_imageid, $hpp_config.image_size)|escape:'html':'UTF-8'}" alt="{$product->link_rewrite}">
{/if}