<section class="user-message-section">
  <?php foreach($messages as $type => $type_messages): ?>
  <div class="group-type-messages <?php echo $type; ?>">
  <?php foreach($type_messages as $ind=>$message): ?>
  <?php 
      $class = '';
      if(0 === (int)$ind){
        $class .= ' first';
      }
      if( count($type_messages) - 1 === (int)$ind){
        $class .= ' last';
      }
    ?>
    <div class="message-wrapper<?php echo $class;?>  row">
      <div class="col-12">
        <?php echo $message; ?>
      </div>
    </div>
  <?php endforeach; ?>
  </div>
  <?php endforeach; ?>
</section>