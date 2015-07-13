<div class="contentwrapper">
	
	<form class="stdform formtable" action="../shop/addWorker" method="post" enctype="multipart/form-data">
		<table width="60%" cellspacing="0">
			<tr>
				<td>
					<p>
			        	<label>姓名：</label>
			            <span class="field"><input type="text" name="staff_name" class="smallinput" /></span>
			        </p>
				</td>
				<td>
					<p>
			        	<label>手机：</label>
			            <span class="field"><input type="text" name="phone_mob" class="smallinput" /></span>
			        </p>
				</td>
			</tr>
			<tr>
				<td>
					<p>
						<label>所属分类：</label>
						<span class="field">
			            	<select name="item_ids[]" id="item_ids" data-placeholder="请选择类型" class="chzn-select" multiple="multiple" style="width:350px;" tabindex="4">
								{html_options options=$ser_items}
							</select>
						</span>
					</p>
				</td>
				<td>
					<p>
						<label>所属商家：</label>
					</p>
					<span class="field">
		            	<select name="shop_id" id="shop_id" data-placeholder="请选择商家" class="chzn-select" style="width:350px;" tabindex="4">
							{html_options options=$shops}
						</select>
                    </span>
				</td>
			</tr>
			<tr>
				<td>
					<p>
						<label>健康证：</label>
					</p>
					<span class="field">
                    	<input type="file" name="health_certificate" />
                    </span>
				</td>
				<td>
					<p>
						<label>健康证到期时间：</label>
					</p>
					<span class="field">
                    	<input type="text" name="health_date" />
                    </span>
				</td>
			</tr>
			<tr>
				<td>
					<p>
						<label>身份证正面：</label>
					</p>
					<span class="field">
                    	<input type="file" name="id_front" />
                    </span>
				</td>
				<td>
					<p>
						<label>身份证反面：</label>
					</p>
					<span class="field">
                    	<input type="file" name="id_reverse" />
                    </span>
				</td>
			</tr>
			<tr>
				<td>
					<p>
						<label>上岗证：</label>
					</p>
					<span class="field">
                    	<input type="file" name="work_license" />
                    </span>
				</td>
				<td>
					&nbsp;
				</td>
			</tr>
		</table>
		<br>
        <p class="stdformbutton">
        	<button id="submitBtn" class="submit radius2">提交</button>

			<!-- 

        	<button type="button" class="btn btn-primary">（首选项）Primary</button>
        	<button type="button" class="btn btn-primary">（首选项）Primary</button>
        	<button type="button" class="btn btn-primary">（首选项）Primary</button> -->
        </p>
	</form>

</div>