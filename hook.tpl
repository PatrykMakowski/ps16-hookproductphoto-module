{*
	Default vars:
		$hpp_imageid (int)
		$hpp_config (array)
		$id_product (int)
		
	Imported from product.tpl:
		$link (object)
		$product (object)
*}
{if $hhp_imageid}
	<img class="img-responsive" {$link->getImageLink($product->link_rewrite, $hhp_imageid, $hpp_config.image_size)|escape:'html':'UTF-8'}" alt="{$product->link_rewrite}">
{/if}