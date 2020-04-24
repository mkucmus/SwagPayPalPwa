<template>
    <div class="paypal-smart-buttons">
        <div id="paypal-button-container"></div>
        <script src="https://www.paypal.com/sdk/js?client-id=AS3Lu_NZ3uRQC1KtYBwBu_rj6KiOdqTBlNjcO9L0alkPVCb2IPMPH3hUvO5VIkI0L1Apc8YiXgOq6zNp&currency=EUR"/>
    </div>
</template>
<script>
  import { pluginGet, pluginPost } from "@shopware-pwa/shopware-6-client"
  export default {
    data() {
      return {
        phraseResponse: null,
        sdkUrl: null
      };
    },
    async beforeMount() {
      const clientIdResponse = await pluginGet({
        code: "paypal",
        resource: "client-id"
      })
    },
    mounted() {
      paypal.Buttons({
        currency: 'EUR',
        createOrder: async function(data, actions) {
          const tokenResponse = await pluginPost({
            code: 'paypal',
            resource: 'create-order'
          })
          console.warn('createOrder', tokenResponse);
          return tokenResponse.data.token;
        },
        onApprove: async function(data, actions) {
          console.warn('onApprove', data, actions);
        }
      }).render('#paypal-button-container');
    }
  };
</script>
<style lang="scss" scoped>
    .hello-cody {
        padding: 20px 5px;
        min-height: 30px;
        text-align: right;

        h3 {
            font-weight: 100;
        }
    }
</style>
