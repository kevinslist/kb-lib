<nav id="footer-contacts-wrapper">
  <ul id="footer-contact-list">
  <?php foreach($contacts as $label=>$item): ?>
    <li class="<?php echo isset($item['class']) ? $item['class'] : '';?>">
      <?php if(is_string($label)): ?>
      <span class="contact-label"><?php echo $label;?>:</span>
      <?php endif; ?>
      <?php if(isset($item['link'])): ?>
      <a class="contact-link" <?php echo isset($item['target']) ? 'target="' . $item['target'] . '"' : ''; ?> href="<?= (isset($item['link_type']) && 'local' == $item['link_type'] ? site_url($item['link']) : $item['link']);?>"><?php echo $item['name'];?></a>
      <?php else: ?>
        <?php echo $item['name'];?>
      <?php endif; ?>
    </li>
  <?php endforeach; ?>
  </ul>
</nav>