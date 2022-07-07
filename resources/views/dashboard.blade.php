<template>

{{ $message }}

<x-dbf-footer :message="message"></x-dbf-footer>

</template>

<script>
	import DbfFooter from './components/DbfFooter.vue';

	export default {
		props: ['message'],

		components: {DbfFooter},

		data(){
			return {
				message: message
			}
		}
	}
</script>