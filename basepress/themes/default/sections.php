<?php
/*
 *	This is the archive page for the top sections with a list style.
 */


//Get the Knowledge Base object
$bpkb_knowledge_base = basepress_kb();
$bpkb_sidebar_position = basepress_sidebar_position( true );
$bpkb_show_sidebar = is_active_sidebar( 'basepress-sidebar' ) && $bpkb_sidebar_position != 'none';
$bpkb_content_classes = $bpkb_show_sidebar ? ' show-sidebar' : '';


//Get active theme header

basepress_get_header( 'basepress' );
?>

<!-- Main BasePress wrap -->
<div class="bpress-wrap">
	
	<div class="bpress-content-area bpress-float-<?php echo esc_attr( $bpkb_sidebar_position ) . esc_attr( $bpkb_content_classes ); ?>">
		
		<!-- Knowledge Base title -->
		<header class="bpress-main-header">
			<h1 class="bpress-product-title bpress-kb-title"><?php echo esc_html( $bpkb_knowledge_base->name ); ?></h1>
		</header>
		
		<!-- Add breadcrumbs -->
		<div class="bpress-crumbs-wrap">
			<?php basepress_breadcrumbs(); ?>
		</div>
		
		<!-- Add searchbar -->
		<div class="bpress-searchbar-wrap">	
			<?php 
			basepress_searchbar(); ?>
		</div>
		
		<!-- Add main content -->
		<main class="bpress-main" role="main">
			<?php
				ob_start();
					basepress_get_template_part( 'sections-content' );
				$sections_content = ob_get_clean();
				echo apply_filters( 'basepress_sections_content', $sections_content, $bpkb_knowledge_base ); // phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped
			?>
		</main>
	</div><!-- content area -->
	
	<!-- Sidebar -->
	<?php if ( $bpkb_show_sidebar ) : ?>
	<aside class="bpress-sidebar bpress-float-<?php echo esc_attr( $bpkb_sidebar_position ); ?>" role="complementary">
		<div class="hide-scrollbars">
		<?php dynamic_sidebar( 'basepress-sidebar' ); ?>
		</div>
	</aside>
	<?php endif; ?>
	
	
</div><!-- wrap -->
<?php basepress_get_footer( 'basepress' ); ?>
