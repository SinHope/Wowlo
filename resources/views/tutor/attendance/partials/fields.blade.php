{{-- Hidden inputs mirrored from the attendancePage Alpine state. Included in BOTH the Save and Add-to-Billing forms. --}}
<template x-for="d in addedList" :key="'a' + d"><input type="hidden" name="added[]" :value="d"></template>
<template x-for="d in removedList" :key="'r' + d"><input type="hidden" name="removed[]" :value="d"></template>
<input type="hidden" name="view" :value="view">
<input type="hidden" name="month" :value="monthKey">
<input type="hidden" name="from" :value="fromField">
<input type="hidden" name="to" :value="toField">
<input type="hidden" name="window_from" :value="windowFrom">
<input type="hidden" name="window_to" :value="windowTo">
