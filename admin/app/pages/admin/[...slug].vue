<script setup lang="ts">
// The old /admin/* surface was retired (client sign-off) — every old URL
// redirects to its /admin/v2/* equivalent so bookmarks keep working.
definePageMeta({
  middleware: [
    (to) => {
      const parts = Array.isArray(to.params.slug) ? to.params.slug : [to.params.slug].filter(Boolean)
      let rest = parts.join('/')

      // Old dashboard was /admin/dashboard; v2's dashboard is the index.
      if (rest === 'dashboard') rest = ''
      // Old availability editor is v2's wizard step 8.
      const avail = rest.match(/^tours\/(\d+)\/availability$/)
      if (avail) return navigateTo(`/admin/v2/tours/${avail[1]}/edit?step=7`, { redirectCode: 301 })

      return navigateTo(`/admin/v2${rest ? '/' + rest : ''}`, { redirectCode: 301 })
    },
  ],
})
</script>

<template>
  <div />
</template>
