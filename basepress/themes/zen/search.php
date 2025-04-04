<?php
/*
 *	This is BasePress archive page for search results.
 */


//Get Knowledge Base object
$bpkb_knowledge_base = basepress_kb();
$bpkb_is_single_kb = basepress_is_single_kb();
$bpkb_sidebar_position = basepress_sidebar_position( true );
$bpkb_show_sidebar = is_active_sidebar( 'basepress-sidebar' ) && $bpkb_sidebar_position != 'none';

//Get Post meta icons
$bpkb_post_meta_icons = basepress_get_post_meta_icons();
$bpkb_post_views_icon = isset( $bpkb_post_meta_icons[0] ) ? $bpkb_post_meta_icons[0] : '';
$bpkb_post_post_like_icon = isset( $bpkb_post_meta_icons[1] ) ? $bpkb_post_meta_icons[1] : '';
$bpkb_post_post_dislike_icon = isset( $bpkb_post_meta_icons[2] ) ? $bpkb_post_meta_icons[2] : '';
$bpkb_post_post_date_icon = isset( $bpkb_post_meta_icons[3] ) ? $bpkb_post_meta_icons[3] : '';

//Get active theme header
basepress_get_header( 'basepress' );
?>

<div class="bpress-wrap">
	<!-- Knowledge Base Title -->
	<?php if( ! $bpkb_is_single_kb ) :?>
		<header class="bpress-main-header">
			<h2 class="bpress-product-title bpress-kb-title"><?php echo esc_html( $bpkb_knowledge_base->name ); ?></h2>
		</header>
	<?php endif; ?>
	
	<!-- Add breadcrumbs -->
	<div class="bpress-crumbs-wrap">
		<?php basepress_breadcrumbs(); ?>
	</div>
	
	<div class="bpress-content-area bpress-float-<?php echo esc_attr( $bpkb_sidebar_position ); ?>">
		
		<!-- Add searchbar -->
		<div class="bpress-card">
			<?php basepress_searchbar(); ?>
		</div>

		<main class="bpress-main" role="main">

			<header class="bpress-post-header">
				<h2><?php echo esc_html( basepress_search_page_title() ) . ' ' . esc_html( basepress_search_term() ); ?></h2>
			</header>

			<div class="bpress-card">
			<?php if ( have_posts() ) { ?>

				<ul class="bpress-card-body">
					
					<?php
					while ( have_posts() ) {
						the_post();
					?>
			
						<li class="bpress-post-link search">
							<h3>
								<!-- Post icon -->
								<?php if ( basepress_show_post_icon() ) { ?>
									<span aria-hidden="true" class="bp-icon <?php echo esc_attr( basepress_post_icon( get_the_ID() ) ) ; ?>"></span>
								<?php } ?>

								<!-- Post permalink -->
								<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							</h3>
							<!-- Post excerpt -->
							<p class="bpress-search-excerpt"><?php basepress_search_post_snippet(); ?></p>

							<?php if( 'knowledgebase' == get_post_type() ) { ?>
								<!-- Post Section -->
								<?php	$bpkb_post_section = get_the_terms( get_the_ID(), 'knowledgebase_cat' )[0]; ?>
								<a href="<?php echo esc_url( get_term_link( $bpkb_post_section ) ); ?>" class="bpress-search-section"><?php echo esc_html( $bpkb_post_section->name ); ?></a>

								<?php

								
								if( basepress_byline() ){

								?>

								<!-- Post Meta -->
								<div class="bpress-post-meta">
									<?php $bpkb_post_metas = basepress_get_post_meta( get_the_ID() ); ?>

									<span class="bpress-post-views"><span class="<?php echo esc_attr( $bpkb_post_views_icon ) ; ?>"></span><?php echo esc_html( $bpkb_post_metas['views'] ) ; ?></span>
									<?php if ( basepress_show_post_votes() ) { ?>
										<span class="bpress-post-likes"><span class="<?php echo esc_attr( $bpkb_post_post_like_icon ) ; ?>"></span><?php echo esc_html( $bpkb_post_metas['votes']['like'] ) ; ?></span>
										<span class="bpress-post-dislikes"><span class="<?php echo esc_attr( $bpkb_post_post_dislike_icon ) ; ?>"></span><?php echo esc_html( $bpkb_post_metas['votes']['dislike'] ) ; ?></span>
									<?php } ?>
									<span class="bpress-post-date"><span class="<?php echo esc_attr( $bpkb_post_post_date_icon ) ; ?>"></span><?php echo esc_html( get_the_date() ) ; ?></span>
									<span class="bpress-post-date"><span class="<?php echo esc_attr( $bpkb_post_post_date_icon ) ; ?>"></span><?php echo esc_html( get_the_modified_date() ) ; ?></span>
								</div>
							<?php }
							}
							?>
						</li>

					<?php } //End while ?>

				</ul>
			<?php
			} else {
				echo '<h3>' . esc_html( basepress_search_page_no_results_title() )  . '</h3>';
			}
			?>

			</div>
		</main>
		
		<!-- Pagination -->
		<nav class="bpress-pagination">
		<?php basepress_pagination(); ?>
		</nav>
		
	</div><!-- content area -->
	
	<!-- BasePress Sidebar -->
	<?php if ( $bpkb_show_sidebar ) : ?>
		<aside class="bpress-sidebar bpress-float-<?php echo esc_attr( $bpkb_sidebar_position ); ?>" role="complementary">
			<div class="hide-scrollbars">
			<?php dynamic_sidebar( 'basepress-sidebar' ); ?>
			</div>
		</aside>
	<?php endif; ?>


</div><!-- wrap -->
<?php basepress_get_footer( 'basepress' ); ?>
