<?php
/* Template Name: Home*/
get_header();
?>


<?php $banner = get_field("homepage_banner");?>

<section class="main-slide" <?php if($banner['image']) :?> style="background-image:url('<?php echo $banner['image']['url'];?>"><?php endif;?>
	<div class="container">
		<div class="row">
		<div class="inner">
			<div class="content">        
				<h1><?php echo $banner['title'];?></h1>
				<?php echo $banner['text'];?>
				<?php foreach($banner['buttons'] as $button):?>
					<a href="<?php echo $button['button_link'];?>" class="btn white" <?php if($button['external_link'] === TRUE) : ?> target="_blank" <?php endif;?>><?php echo $button['button_title'];?></a>
				<?php endforeach;?>
			</div>
		</div>
		</div>
	</div>
</section>


<?php if(have_posts()): 
		while(have_posts()): the_post(); ?>
<?php the_content(); ?>
<?php endwhile;
	endif; ?>


<?php get_footer();?>