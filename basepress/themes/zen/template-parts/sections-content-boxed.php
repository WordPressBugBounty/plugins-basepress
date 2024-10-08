<?php
/*
 *	This template lists all sections for a product
 *
 */

//Get the sections object
$bpkb_sections = basepress_sections();

?>
<div class="bpress-grid">

<?php
//We can iterate through the sections
foreach ( $bpkb_sections as $bpkb_section ) :
?>
	
	<div class="bpress-col bpress-col-<?php basepress_section_cols(); ?>">
		<div class="bpress-card bpress-section fix-height">
			<a href="<?php echo esc_url( $bpkb_section->permalink ); ?>">
				
				<!-- Section Image -->
				<?php if ( $bpkb_section->image['image_url'] ) { ?>
					<img class="bpress-card-top-image small" src="<?php echo esc_url( $bpkb_section->image['image_url'] ); ?>" alt="<?php echo esc_html( $bpkb_section->name ); ?>">
				<?php } else { ?>
					<div class="bpress-card-top-image small no-image">
						<span aria-hidden="true" class="bpress-section-icon <?php echo esc_attr( $bpkb_section->icon ); ?>"></span>
					</div>
				<?php } ?>

				<!-- Section Title -->
				<h2 class="bpress-card-title"><?php echo esc_html( $bpkb_section->name ); ?>	</h2>
				
				<!-- Section Description -->
				<div class="bpress-card-body">
					<?php if ( $bpkb_section->description ) { ?>
					<p><?php echo wp_kses_post( $bpkb_section->description ); ?></p>
					<?php } ?>
				</div>

				<!-- Section View All -->
				<span class="bpress-card-footer">
					<?php basepress_section_view_all( $bpkb_section->posts_count ); ?>
				</span>

			</a>

		</div><!-- End Section -->
	</div><!-- End col -->

<?php endforeach; ?>

</div><!-- End grid -->
