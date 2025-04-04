<?php
/* Template Name: Right Sidebar */

//Get Knowledge Base object
$bpkb_knowledge_base = basepress_kb();


basepress_get_header( 'basepress' );
?>

<!-- Main BasePress wrap -->
<div class="bpress-wrap">
	
	<div class="bpress-content-area bpress-float-left">
		
		<!-- Knowledge Base title -->
		<header class="bpress-main-header">
			<h2 class="bpress-product-title bpress-kb-title"><?php echo esc_html( $bpkb_knowledge_base->name ); ?></h2>
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
			//Start the loop.
			while ( have_posts() ) : the_post();

				//Include the page content template using basepress internal function.
				basepress_get_template_part( 'post-content' );

				//Get Polls Items
				basepress_votes();

			//End of the loop.
			endwhile;

			?>

			<!-- Add previous and next articles navigation -->
			<?php basepress_get_template_part( 'adjacent-articles' ); ?>

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
	<aside class="bpress-sidebar bpress-float-left" role="complementary">
		<div class="hide-scrollbars">
		<?php dynamic_sidebar( 'basepress-sidebar' ); ?>
		</div>
	</aside>
	<?php endif; ?>
	
</div><!-- .wrap -->

<?php basepress_get_footer( 'basepress' ); ?>
