<?php
    $this->out->elementStart('aside', array('id'=>'profile'));

	$profile = $this->action->subject->getProfile();
    $this->widget('Profile', array('item'=>$profile));
	$minilistargs = array('avatarSize'=>Avatar::MINI_SIZE, 'mini'=>true, 'item'=>$profile, 'pagination'=>false, 'itemTag'=>'li');
	if ($profile->isUser()) {
		$this->widget('SubscriptionList', array_merge($minilistargs, array('title'=>_m('Subscriptions'),
											'titleLink'=>common_local_url('subscriptions', array('nickname'=>$profile->nickname)),
											)));
		$this->widget('SubscriberList', array_merge($minilistargs, array('title'=>_m('Subscribers'),
											'titleLink'=>common_local_url('subscribers', array('nickname'=>$profile->nickname)),
											)));
		$this->widget('GroupList', array_merge($minilistargs, array('title'=>_m('Group memberships'),
											'titleLink'=>common_local_url('usergroups', array('nickname'=>$profile->nickname)),
											)));
	} elseif ($profile->isGroup()) {
		$this->widget('MemberList', array_merge($minilistargs, array('title'=>_m('Members'),
											'titleLink'=>common_local_url('groupmembers', array('nickname'=>$profile->nickname)),
											)));
	}

    $this->out->elementEnd('aside');
