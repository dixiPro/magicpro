<script setup>
import { ref, computed, onMounted, onUnmounted, watch, useId } from "vue";
import { useToast } from "primevue/usetoast";
const toast = useToast();

import EditArticle from "./component/EditArticle.vue";

import { useConfirm } from "primevue/useconfirm";
const confirm = useConfirm();

const articleId = ref(1);
const ready = ref(false);

onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    const obj = Object.fromEntries(params.entries());
    articleId.value = Number(location.hash.slice(1)) || 1;
    history.pushState(articleId.value, null, "#" + articleId.value);
    console.log("--start ediror--");
    ready.value = true;

    window.addEventListener("popstate", onPopState);

    // document.showToast('Сообщение');
    // document.confirmDialog('Сохранить?');

    // глобальные сервисы диалог подтверждения и тосты
    document.showToast = (msg = "", severity = "success") => {
        const life = severity === "success" ? 5000 : 100000;
        toast.add({ severity: severity, detail: msg, life: life });
        if (severity === "error") {
            console.log(msg);
        }
    };
    document.confirmDialog = async (message) => {
        return new Promise((resolve, reject) => {
            confirm.require({
                message,
                header: "",
                icon: "fas fa-question",
                acceptLabel: "Да",
                rejectLabel: "Нет",
                accept: () => resolve(true),
                reject: () => resolve(false), // или reject(), если хотите ошибку
            });
        });
    };
});

onUnmounted(() => {
    window.removeEventListener("popstate", onPopState);
});

const onPopState = (event) => {
    // event.state — это то, что передавали первым аргументом в pushState
    // location.hash — текущий хеш в адресе
    articleId.value = Number(location.hash.slice(1)) || 1;
};
</script>
<template>
    <EditArticle v-model:articleId="articleId" v-if="ready"></EditArticle>

    <!-- тосты -->
    <Toast position="bottom-right"></Toast>
    <!-- Дилог Да Нет -->
    <ConfirmDialog></ConfirmDialog>
</template>

<style>
.pointer {
    cursor: pointer;
}

.icon-menue {
    cursor: pointer;
    font-size: 28px;
}

.icon-border {
    border: 1px solid #777;
}
</style>
