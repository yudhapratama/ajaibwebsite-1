<div class="box bg-white" ng-controller="TransactionCategoryController" ng-init="getEdit()">
    <div class="box-header bg-transparent">
        <h3 class="box-title">
            <i class="fontello-th-large-outline"></i>
            <span>Transaction Category - Update</span>
        </h3>
    </div>
    
    <div class="box-body pad-forty" style="display: block;">
        <form class="form-horizontal" novalidate name="form_edit" ng-submit="ActEdit(form_edit.$valid)">
            <input type="hidden" ng-model="form.id" ng-value="currentId">
            <div class="form-group" ng-class="{'has-error' : form_edit.name.$invalid && submitted }">
                <label for="inputName" class="col-sm-2 control-label">Nama</label>
                <div class="col-sm-6">
                    <input type="text" class="form-control" ng-required="true" id="inputName" name="name" ng-model="form.name">
                </div>
                <p ng-show="form_edit.name.$invalid && submitted" class="help-block">Name is required.</p>
            </div>
            <div class="form-group">
                <label for="inputDeskripsi" class="col-sm-2 control-label">Deskripsi</label>
                <div class="col-sm-6">
                    <input type="text" ng-model="form.description" class="form-control" id="inputDeskripsi" name="description" ng-model="form.description">
                </div>
            </div>                
            <div class="form-group">
                <button class="btn btn-primary" type="submit" ><i class="glyphicon glyphicon-floppy-disk"></i> Simpan</button>
                <a class="btn btn-default" type="button" ng-href="/transaction-categories"><i class="glyphicon glyphicon-floppy-remove"></i>  Batal</a>
            </div>
        </form>
    </div>
</div>
