<?php
declare(strict_types=1);
// Run against an isolated test database via tools/run_backend_tests.py.
if(!str_starts_with(getenv('CLINIC_DB_NAME')?:'','wellnest_lab2_test_')) {fwrite(STDERR,"Use tools/run_backend_tests.py to run against an isolated database.\n");exit(1);}
ob_start();
$_SERVER['SCRIPT_NAME']='/medical_clinic/admin/index.php';
require __DIR__.'/../app/bootstrap.php';require __DIR__.'/../app/service_management.php';require __DIR__.'/../app/appointments.php';
set_error_handler(static function($severity,$message,$file,$line){throw new ErrorException($message,0,$severity,$file,$line);});
$_SESSION['user_id']=1;$checks=[];
function check(bool $result,string $label): void {global $checks;if(!$result)throw new RuntimeException('FAIL: '.$label);$checks[]=$label;echo 'PASS: '.$label.PHP_EOL;}
check(db()->getAttribute(PDO::ATTR_DRIVER_NAME)==='mysql','Application uses the MySQL PDO driver');
check(count(services())===6&&count(categories())===4,'Six services and four supporting categories are seeded');
check((int)query('SELECT COUNT(*) FROM users')->fetchColumn()===4,'User and role sample data exist');
foreach(['admin@wellnest.example'=>'WellnestAdmin!2026','patient@wellnest.example'=>'WellnestPatient!2026'] as $email=>$password)check(password_verify($password,query('SELECT password_hash FROM users WHERE email=?',[$email])->fetchColumn()),'SQL demo password verifies: '.$email);
check((int)query('SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE()')->fetchColumn()===4,'SQL import creates all four foreign key relationships');
check(count(query('SHOW TABLES')->fetchAll())===6,'All six relational tables are present');
$admin=current_user();check($admin['role']==='admin','Current user role is loaded from the database');
$oldSession=session_id();$oldCsrf=csrf_token();login_user($admin);check(session_id()!==$oldSession&&csrf_token()!==$oldCsrf,'Successful login rotates the session ID and CSRF token');
check(base_url('auth/login.php')==='/medical_clinic/auth/login.php','Nested admin URLs resolve to the project root');
check(find_service('1 OR 1=1')===null&&find_service(['1'])===null&&find_service('0')===null,'Invalid service identifiers and SQL injection are rejected');
check(e('<script>"')==='&lt;script&gt;&quot;','Output encoding prevents raw HTML injection');
check(safe_next('https://evil.example/',$admin)==='admin/index.php','External login redirect is rejected');
check(safe_next('admin/services.php',['role'=>'patient'])==='pages/dashboard.php','Patient redirect cannot target the admin workspace');
[$errors,$values,$password]=validate_registration(['full_name'=>'Unicode Huỳnh','email'=>'NEW@example.com','password'=>'StrongPass123!','password_confirmation'=>'StrongPass123!','consent'=>'1','role'=>'admin']);
check(!$errors&&$values['email']==='new@example.com','Registration accepts Unicode names and normalizes email');
$hash=password_hash($password,PASSWORD_DEFAULT);check($hash!==$password&&password_verify($password,$hash),'Passwords are hashed and verified securely');
foreach([['email','bad'],['full_name','<script>'],['password','short'],['password',str_repeat('a',73).'1'],['password',"Valid123\0"],['password_confirmation','different'],['consent','0']] as [$field,$value]){
 $input=['full_name'=>'New User','email'=>'new@example.com','password'=>'StrongPass123!','password_confirmation'=>'StrongPass123!','consent'=>'1'];$input[$field]=$value;[$invalid]=validate_registration($input);check(isset($invalid[$field]),'Registration rejects invalid '.$field.' ('.count($checks).')');
}
$duplicate=false;try{query('INSERT INTO users(full_name,email,password_hash) VALUES (?,?,?)',['Duplicate','ADMIN@wellnest.example',$hash]);}catch(PDOException $error){$duplicate=$error->getCode()==='23000';}check($duplicate,'Unique email prevents case-insensitive duplicate accounts');
$service=find_service('1',true);$form=$service;$form['includes']=implode("\n",json_decode($service['includes_json'],true));foreach($form as $key=>$value)if(is_int($value))$form[$key]=(string)$value;
[$errors,$values]=validate_service($form);check(!$errors,'Valid service form passes server validation');
foreach([['price','-1'],['price','1.5'],['duration','0'],['category_id','999'],['image','../../config/local.php'],['name','<script>'],['includes',''],['is_active','7']] as [$field,$value]){$input=$form;$input[$field]=$value;[$invalid]=validate_service($input);check(isset($invalid[$field]),'Service validation rejects invalid '.$field.' ('.count($checks).')');}
$values['name']='Backend CRUD Service';$id=save_service($values);check(find_service((string)$id)['name']==='Backend CRUD Service','Create stores a new MySQL service');
$values['price']='275000';$values['version']='1';save_service($values,$id);check((int)find_service((string)$id)['price']===275000,'Update changes the same record read by public pages');
$conflict=false;try{save_service($values,$id);}catch(DomainException $error){$conflict=true;}check($conflict,'Optimistic version check prevents overwriting a newer edit');
$values['version']='2';$values['is_active']='0';save_service($values,$id);check(find_service((string)$id)===null&&find_service((string)$id,true)!==null,'Drafts are hidden from public reads but remain available to admins');
delete_service($id);check(find_service((string)$id,true)===null,'Intentional deletion removes an unreferenced service');
$referenced=false;try{delete_service(1);}catch(DomainException $error){$referenced=true;}check($referenced&&find_service('1')!==null,'Foreign keys preserve services referenced by appointment history');
$date=new DateTimeImmutable('tomorrow');while($date->format('w')==='0')$date=$date->modify('+1 day');
$input=['service_id'=>'1','full_name'=>'Backend Demo','email'=>'backend@example.com','preferred_date'=>$date->format('Y-m-d'),'preferred_time'=>'09:00','consent'=>'1'];
[$errors,$appointmentValues,$selected]=validate_appointment($input);check(!$errors,'Valid appointment request passes server validation');
foreach([['preferred_date',"2026-11\0-02"],['preferred_date',str_repeat('1',1000)],['email',str_repeat('a',64).'@'.str_repeat('b',63).'.'.str_repeat('c',61).'.com'],['preferred_date','2026-02-31'],['preferred_date',date('Y-m-d')],['preferred_date',(new DateTimeImmutable('next Sunday'))->format('Y-m-d')],['preferred_time','03:00'],['service_id','999'],['consent','']] as [$field,$value]){$invalidInput=$input;$invalidInput[$field]=$value;[$invalid]=validate_appointment($invalidInput);check(isset($invalid[$field]),'Appointment rejects invalid '.$field.' ('.count($checks).')');}
$key=bin2hex(random_bytes(24));$first=save_appointment($appointmentValues,$selected,$key);$second=save_appointment($appointmentValues,$selected,$key);
check($first==$second,'Appointment retry is idempotent');
check((int)$first['quoted_price']===250000,'Appointment fee is read from the database, not client input');
query('UPDATE services SET price=price+10000,name=? WHERE id=1',['Changed after request']);
check((int)query('SELECT quoted_price FROM appointment_requests WHERE request_key=?',[$key])->fetchColumn()===250000 && query('SELECT service_name FROM appointment_requests WHERE request_key=?',[$key])->fetchColumn()===$selected['name'],'Service edits preserve historical request name and fee snapshots');
query('UPDATE services SET price=250000,name=? WHERE id=1',[$selected['name']]);
$record=query('SELECT * FROM appointment_requests WHERE request_key=?',[$key])->fetch();
$blocked=false;try{change_appointment_status((int)$record['id'],'cancelled',1,['id'=>2,'role'=>'patient']);}catch(DomainException $error){$blocked=true;}check($blocked,'A patient cannot alter another account’s request');
change_appointment_status((int)$record['id'],'confirmed',1,$admin);check(query('SELECT status FROM appointment_requests WHERE id=?',[$record['id']])->fetchColumn()==='confirmed','Admin can move a requested appointment to confirmed');
$blocked=false;try{change_appointment_status((int)$record['id'],'completed',1,$admin);}catch(DomainException $error){$blocked=true;}check($blocked,'Stale appointment status updates are rejected');
change_appointment_status((int)$record['id'],'completed',2,$admin);
$blocked=false;try{change_appointment_status((int)$record['id'],'requested',3,$admin);}catch(DomainException $error){$blocked=true;}check($blocked,'Closed appointments cannot return to an earlier status');
$patient=['id'=>2,'role'=>'patient'];$own=query("SELECT * FROM appointment_requests WHERE user_id=2 AND status='requested' LIMIT 1")->fetch();
$blocked=false;try{change_appointment_status((int)$own['id'],'confirmed',(int)$own['version'],$patient);}catch(DomainException $error){$blocked=true;}check($blocked,'Patients cannot confirm their own requests');
change_appointment_status((int)$own['id'],'cancelled',(int)$own['version'],$patient);
check(query('SELECT status FROM appointment_requests WHERE id=?',[$own['id']])->fetchColumn()==='cancelled','Patients can cancel their own pending requests');
$blocked=false;try{change_appointment_status((int)$own['id'],'cancelled',(int)$own['version']+1,$patient);}catch(DomainException $error){$blocked=true;}check($blocked,'Cancelled requests cannot be changed again');
check((int)query('SELECT COUNT(*) FROM audit_logs')->fetchColumn()>1,'Mutating operations produce an audit trail');
echo PHP_EOL.'All '.count($checks).' backend checks passed.'.PHP_EOL;
file_put_contents(__DIR__.'/../report/lab2/backend-verification.json',json_encode(['executed_at'=>date(DATE_ATOM),'passed'=>count($checks),'checks'=>$checks],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
