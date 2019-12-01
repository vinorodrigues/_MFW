<?php

include_once 'config.php';
include_once 'inc/core.php';

add_style('signin', "
	html {
		height: 100%;
	}

	body {
		height: 100%;
		background-color: #eee;
	}

	body {
		background-image: url('app/bg-login.jpg');
		background-position: center center;
		background-repeat:  no-repeat;
		background-attachment: fixed;
		background-size:  cover;
		background-color: #999;
	}

	.login-ext { }
	.login-int { }
	.login-box { box-shadow: 5px 5px 7px rgba(33,33,33,.7); }
	.login-backdrop {
		position: fixed;
		left: 0;
		right: 0;
		width: 100%;
		height: 100%;
		background: radial-gradient(circle, rgba(0,0,0,0) 10%, rgba(0,0,0,1) 100%);
		z-index: -1040;
	}

	.login-ext { display: table; height: 100%; margin: 0 auto; }
	.login-int { display: table-cell; vertical-align: middle; }
	.login-box { width:24rem; }
");

include 'themes/' . APP_THEME . '/top.php';
?>

<div class="login-backdrop"></div>
<div class="login-ext">
	<div class="login-int">
		<div class="card login-box">
			<h5 class="card-header">Signin</h5>
			<div class="card-body">

				<form class="form" role="form" autocomplete="off" id="loginForm" novalidate="" method="POST">
					<div class="form-group">
						<div class="input-group">
							<div class="input-group-prepend">
								<span class="input-group-text"><i class="fas fa-user"></i></span>
							</div>
							<input type="text" class="form-control" name="username" id="username" placeholder="Username or email" required>
						</div>
					</div>
					<div class="form-group">
						<div class="input-group">
							<div class="input-group-prepend">
								<span class="input-group-text"><i class="fas fa-lock"></i></span>
							</div>
							<input type="password" class="form-control" id="password" placeholder="Password" autocomplete="password" required>
							<div id="pwd-visibility" class="input-group-append d-none">
								<span class="input-group-text">
									<a href="#" id="pwd-show"><i class="fas fa-fw fa-eye"></i></a>
									<a href="#" id="pwd-hide" class="d-none"><i class="fas fa-fw fa-eye-slash"></i></a>
								</span>
							</div>
						</div>
					</div>
					<div class="form-check small">
						<label class="form-check-label">
							<input type="checkbox" class="form-check-input"> <span>Remember me</span>
						</label>
					</div>
					<button type="submit" class="btn btn-success float-right" id="btnLogin">Login</button>
				</form>

			</div>
		</div>
	</div>
</div>



<?php

add_script('signin', "
	$(document).ready(function() {
		$('#pwd-visibility').removeClass('d-none');
		$('#pwd-show').on('click', function(event) {
			event.preventDefault();
			$('#password').attr('type', 'text');
			$('#pwd-show').addClass('d-none');
			$('#pwd-hide').removeClass('d-none');
		});
		$('#pwd-hide').on('click', function(event) {
			event.preventDefault();
			$('#password').attr('type', 'password');
			$('#pwd-hide').addClass('d-none');
			$('#pwd-show').removeClass('d-none');
		});
	});
");

include 'themes/' . APP_THEME . '/bottom.php';
?>
