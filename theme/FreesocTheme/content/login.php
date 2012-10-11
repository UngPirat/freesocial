<?php

	if (Event::handle('StartLoginPage', array($this->action))) {
		LoginForm::run();
		Event::handle('EndLoginPage', array($this->action));
	}

