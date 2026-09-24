<?php
/* Template Name: Contact */
/**
 * Contact — Figma 384:2873. Heading, then a two-column row (intro + contact
 * details + stockist callout on the left, the form panel on the right) and a
 * map band. Every section hides itself when its fields are empty.
 */

get_header();

$page_id = get_queried_object_id();
$has_acf = function_exists( 'get_field' );
$heading = $has_acf ? trim( (string) get_field( 'heading', $page_id ) ) : '';
$heading = '' !== $heading ? $heading : get_the_title( $page_id );
$intro   = $has_acf ? (string) get_field( 'intro', $page_id ) : '';
?>

<main class="page-wrapper contact-page">
	<div class="contact-page__inner">

		<h1 class="contact-page__title"><?php echo nl2br( esc_html( $heading ) ); ?></h1>

		<div class="contact-page__cols">
			<div class="contact-page__col contact-page__col--info">
				<?php if ( $intro ) : ?>
					<p class="contact-page__intro"><?php echo nl2br( esc_html( $intro ) ); ?></p>
				<?php endif; ?>

				<?php get_template_part( 'template-parts/contact/details', null, array( 'page_id' => $page_id ) ); ?>
				<?php get_template_part( 'template-parts/contact/callout', null, array( 'page_id' => $page_id ) ); ?>
			</div>

			<div class="contact-page__col contact-page__col--form">
				<?php get_template_part( 'template-parts/contact/form', null, array( 'page_id' => $page_id ) ); ?>
			</div>
		</div>

		<?php get_template_part( 'template-parts/contact/map', null, array( 'page_id' => $page_id ) ); ?>
	</div>

<?php
get_footer();
