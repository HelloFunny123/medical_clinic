<?php
declare(strict_types=1);
function image_options(): array {
    return ['general-care.jpg'=>'General healthcare','dental-care.jpg'=>'Dental care','heart-care.jpg'=>'Heart health','skin-care.jpg'=>'Skin care','child-care.jpg'=>'Child wellness','health-screening.jpg'=>'Preventive care'];
}
function validate_service(array $input): array {
    $fields=['name','category_id','price','duration','image','alt','tag','summary','description','includes','prepare','is_active','version'];
    $values=[]; foreach($fields as $field) $values[$field]=input_text($input,$field);
    $errors=[];
    foreach(['name'=>[3,100],'alt'=>[10,240],'tag'=>[3,80],'summary'=>[10,240],'description'=>[30,6000],'prepare'=>[10,1000]] as $field=>[$min,$max]) {
        if(mb_strlen($values[$field])<$min || mb_strlen($values[$field])>$max || preg_match('/[<>\x00-\x08]/u',$values[$field])) $errors[$field]="Use {$min}–{$max} characters without HTML markup.";
    }
    $categoryIds=array_map('strval',array_column(categories(),'id'));
    if(!in_array($values['category_id'],$categoryIds,true)) $errors['category_id']='Choose an existing category.';
    if(!preg_match('/^[1-9][0-9]{0,7}$/D',$values['price'])) $errors['price']='Enter a whole-number VND fee from 1 to 99,999,999.';
    if(!ctype_digit($values['duration']) || (int)$values['duration']<5 || (int)$values['duration']>240) $errors['duration']='Choose a duration between 5 and 240 minutes.';
    if(!array_key_exists($values['image'],image_options())) $errors['image']='Choose an image from the clinic library.';
    $included=array_values(array_filter(array_map('trim',explode("\n",$values['includes'])),static fn($line)=>$line!==''));
    if(count($included)<1 || count($included)>8) $errors['includes']='Add between 1 and 8 included items, one per line.';
    foreach($included as $line) if(mb_strlen($line)>180 || preg_match('/[<>]/u',$line)) $errors['includes']='Each included item must be plain text up to 180 characters.';
    if(!in_array($values['is_active'],['0','1'],true)) $errors['is_active']='Choose a valid publication status.';
    $values['includes_json']=json_encode($included,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
    return [$errors,$values];
}
function save_service(array $values,?int $id=null): int {
    $parameters=[];foreach(['name','category_id','price','duration','image','alt','tag','summary','description','includes_json','prepare','is_active'] as $field) $parameters[]=$values[$field];
    db()->beginTransaction();
    try {
        if($id) {
            $parameters[]=$id;$parameters[]=(int)$values['version'];
            $statement=query('UPDATE services SET name=?,category_id=?,price=?,duration=?,image=?,alt=?,tag=?,summary=?,description=?,includes_json=?,prepare=?,is_active=?,version=version+1 WHERE id=? AND version=?',$parameters);
            if($statement->rowCount()!==1) throw new DomainException('This service changed in another tab. Reload the page and apply your changes again.');
        } else {
            query('INSERT INTO services (name,category_id,price,duration,image,alt,tag,summary,description,includes_json,prepare,is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',$parameters);
            $id=(int)db()->lastInsertId();
        }
        audit('service.saved','service',$id,'Saved service: '.$values['name']);
        db()->commit();return $id;
    } catch(Throwable $error) { if(db()->inTransaction()) db()->rollBack();throw $error; }
}
function delete_service(int $id): void {
    db()->beginTransaction();
    try {
        $service=find_service((string)$id,true);
        if(!$service) throw new DomainException('This service no longer exists.');
        query('DELETE FROM services WHERE id=?',[$id]);
        audit('service.deleted','service',$id,'Deleted service: '.$service['name']);
        db()->commit();
    } catch(PDOException $error) {
        if(db()->inTransaction()) db()->rollBack();
        if($error->getCode()==='23000') throw new DomainException('This service has appointment requests. Set it to Draft to keep existing records intact.');
        throw $error;
    } catch(Throwable $error) { if(db()->inTransaction()) db()->rollBack();throw $error; }
}
