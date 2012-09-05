<?php
    $this->out->elementStart('aside', array('id'=>'profile'));

    $this->widget('Profile', array('profile'=>$this->action->profile));

    $this->out->elementEnd('aside');
