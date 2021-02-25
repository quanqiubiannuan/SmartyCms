let modals = [];
let deleteId;

/**
 * 获取指定的弹窗对象
 * @param modalName 弹窗id
 * @returns {*}
 */
function getModal(modalName) {
    if (modals[modalName] === undefined) {
        modals[modalName] = new bootstrap.Modal(document.getElementById(modalName), {
            keyboard: false
        });
    }
    return modals[modalName];
}

/**
 * 显示删除提示框
 * @param id
 * @returns {boolean}
 */
function smartyAdminDelete(id) {
    getModal('deleteModal').toggle();
    deleteId = id;
    return false;
}

/**
 * 确认删除
 */
function smartyAdminDeleteTrue() {
    getModal('deleteModal').hide();
    if (deleteId !== undefined) {
        let href = document.getElementById('smartyAdminDelete' + deleteId).attributes['href'].value;
        location.href = href;
    }
}

/**
 * 选择菜单规则
 * @param e
 * @param id
 * @param pid
 * @param step
 */
function smartyAdminRule(e, id, pid, step) {
    if (e.checked) {
        // 选中
        if (step === 1) {
            // 子元素都选中
            let eles = document.getElementsByClassName('smarty-admin-rule-pid' + id);
            let len = eles.length;
            for (let i = 0; i < len; i++) {
                eles[i].checked = true;
                let cid = eles[i].dataset.id;
                let cpid = eles[i].dataset.pid;
                smartyAdminRule(eles[i], cid, cpid, 1);
            }
            step = 2;
        }
        if (step === 2) {
            // 父元素选中
            eles = document.getElementsByClassName('smarty-admin-rule-id' + pid);
            len = eles.length;
            for (let i = 0; i < len; i++) {
                eles[i].checked = true;
                let cid = eles[i].dataset.id;
                let cpid = eles[i].dataset.pid;
                if (cpid > 0) {
                    smartyAdminRule(eles[i], cid, cpid, 2);
                }
            }
        }
    } else {
        // 未选中
        // 子元素都不选中
        let eles = document.getElementsByClassName('smarty-admin-rule-pid' + id);
        let len = eles.length;
        for (let i = 0; i < len; i++) {
            eles[i].checked = false;
            let cid = eles[i].dataset.id;
            let cpid = eles[i].dataset.pid;
            smartyAdminRule(eles[i], cid, cpid, 1);
        }
    }
}
