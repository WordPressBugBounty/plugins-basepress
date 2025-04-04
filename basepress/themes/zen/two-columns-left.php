<?php
/* Template Name: Left Sidebar*/

//Get Knowledge Base object
$bpkb_knowledge_base = basepress_kb();
$bpkb_is_single_kb = basepress_is_single_kb();

basepress_get_header( 'basepress' );
?>

<!-- Main BasePress wrap -->
<div class="bpress-wrap">

	<!-- Knowledge Base title -->
	<?php if( ! $bpkb_is_single_kb ) :?>
		<header class="bpress-main-header">
			<h2 class="bpress-product-title bpress-kb-title"><?php echo esc_html( $bpkb_knowledge_base->name ); ?></h2>
		</header>
	<?php endif; ?>

	<!-- Add breadcrumbs -->
	<div class="bpress-crumbs-wrap">
		<?php basepress_breadcrumbs(); ?>
	</div>

	<div class="bpress-content-area bpress-float-right">
		
		<!-- Add searchbar -->
		<div class="bpress-card">
			<?php 
			basepress_searchbar(); ?>
		</div>
		
		<!-- Add main content -->
		<main class="bpress-main" role="main">

			<?php
			//Start the loop.
			while ( have_posts() ) : the_post();

				//Include the page content template using basepress internal function.
				basepress_get_template_part( 'post-content' );

			//End of the loop.
			endwhile;

			?>

		</main>

		<?php
		// If comments are open or we have at least one comment, load up the comment template.
		if ( comments_open() || get_comments_number() ) {
			comments_template();
		}
		?>

	</div><!-- content area -->
	
	<!-- Sidebar -->
	<?php if ( is_active_sidebar( 'basepress-sidebar' ) ) : ?>
	<aside class="bpress-sidebar bpress-float-right" role="complementary">
		<div class="hide-scrollbars">
		<?php dynamic_sidebar( 'basepress-sidebar' ); ?>
		</div>
	</aside>
	<?php endif; ?>
	
</div><!-- wrap -->

<?php basepress_get_footer( 'basepress' ); ?>
