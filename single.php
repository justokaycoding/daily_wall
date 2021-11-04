<?php require_once(  plugin_dir_path( __FILE__ ) . '/includes/sql.php'); ?>
<?php $dw = new dw_sql(); ?>
<?php $text = buildCloud($dw->getData()); ?>
<?php wp_head(); ?>

<body>
  <div class="word_cloud">
    <?php echo $text;?>
  </div>
  <div class="container">
  	<div class="container__item dailyWall">
  		<form method="post" class="form">
  			<input type="text" class="form__field" maxlength="10"/>
  			<input type="submit" class="btn btn--primary btn--inside uppercase" value="Write" >
  		</form>
      <div class="container__item container__item--bottom">
        <p>Instead of worrying about what you cannot control <br/>shift your energy to what you can create.</p>
      </div>
  	</div>
  </div>
</body>

<?php wp_footer(); ?>
