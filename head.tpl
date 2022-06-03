<div class="d-flex">
              <a class="header-brand" href="./">
                サイト名
              </a>
              <div class="d-flex order-lg-2 ml-auto">
                
				<!--{def admin_name}-->
                <div class="dropdown">
                  <a href="#" class="nav-link pr-0 leading-none" data-toggle="dropdown">
                    <span class="ml-2 d-none d-lg-block">
                      <span class="text-default">{val admin_name}さん</span>
                      <!--small class="text-muted d-block mt-1">{val admin_flg}</small-->
                    </span>
                  </a>
                   
                  <div class="dropdown-menu dropdown-menu-right dropdown-menu-arrow">
                    <a class="dropdown-item" href="./?act=logut">
                      <i class="dropdown-icon fe fe-log-out">o</i> ログアウト
                    </a>
                  </div>
                </div>
                <!--{/def}-->
              </div>
              <a href="#" class="header-toggler d-lg-none ml-3 ml-lg-0" data-toggle="collapse" data-target="#headerMenuCollapse">
                <span class="header-toggler-icon"></span>
              </a>
            </div>