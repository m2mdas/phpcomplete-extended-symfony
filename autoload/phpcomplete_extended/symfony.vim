"=============================================================================
" AUTHOR:  Mun Mun Das <m2mdas at gmail.com>
" FILE: symfony.vim
" Last Modified: September 13, 2013
" License: MIT license  {{{
"     Permission is hereby granted, free of charge, to any person obtaining
"     a copy of this software and associated documentation files (the
"     "Software"), to deal in the Software without restriction, including
"     without limitation the rights to use, copy, modify, merge, publish,
"     distribute, sublicense, and/or sell copies of the Software, and to
"     permit persons to whom the Software is furnished to do so, subject to
"     the following conditions:
"
"     The above copyright notice and this permission notice shall be included
"     in all copies or substantial portions of the Software.
"
"     THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
"     OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
"     MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
"     IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
"     CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
"     TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
"     SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
" }}}
"=============================================================================


let s:save_cpo = &cpo
set cpo&vim

if exists('g:loaded_phpcomplete_extended') && !g:loaded_phpcomplete_extended
    finish
endif
let g:loaded_phpcomplete_extended_symofny = 1

let s:symfony_plugin = {
            \    'name': 'symfony'
            \}

if !exists("s:symfony_index")
    let s:symfony_index = {}
endif

function! phpcomplete_extended#symfony#define() "{{{
    let is_symfony_project = filereadable('app/AppKernel.php') "check this is a valid symfony project

    if is_symfony_project
        return s:symfony_plugin
    endif
    return {}
endfunction "}}}

function! s:symfony_plugin.init() "{{{

endfunction "}}}

function! s:symfony_plugin.is_valid_for_project() "{{{
endfunction "}}}

function! s:symfony_plugin.set_index(index) "{{{
    call s:set_index(a:index)
endfunction "}}}

function! s:set_index(index) "{{{
    let s:symfony_index = a:index
endfunction "}}}

function! s:symfony_plugin.resolve_fqcn(fqcn) "{{{
    return a:fqcn
endfunction "}}}

function! s:symfony_plugin.get_fqcn(parentFQCN, token_data) "{{{
    return s:get_fqcn(a:parentFQCN, a:token_data)
endfunction "}}}

function! s:symfony_plugin.get_menu_entries(fqcn, base, is_this, is_static) "{{{
    return []
endfunction "}}}

function! s:symfony_plugin.get_inside_quote_menu_entries(parentFQCN, token_data) "{{{
    return s:get_inside_quote_menu_entries(a:parentFQCN, a:token_data)
endfunction "}}}

function! s:get_fqcn(parentFQCN, token_data) "{{{

    let methodPropertyText = a:token_data['methodPropertyText']
    let isMethod = has_key(a:token_data, 'isMethod')? a:token_data.isMethod : 0
    let insideBraceText = substitute(s:trim_quote(a:token_data['insideBraceText']), '\\', '', 'g')

    let containerAwareFQCN = 'Symfony\Component\DependencyInjection\ContainerAwareInterface'
    let containerFQCN      = 'Symfony\Component\DependencyInjection\ContainerInterface'
    let controllerFQCN     = 'Symfony\Bundle\FrameworkBundle\Controller\Controller'
    let objectManagerFQCN  = 'Doctrine\Common\Persistence\ObjectManager'

    let symfony_index = s:symfony_index
    let fqcn = ""

    if (phpcomplete_extended#isClassOfType(a:parentFQCN, containerAwareFQCN)
        \ || phpcomplete_extended#isClassOfType(a:parentFQCN, containerFQCN)
        \ )
        \ && isMethod && methodPropertyText == "get"
        \ && has_key(symfony_index['services']['public'], insideBraceText)
        return symfony_index['services']['public'][insideBraceText]['service_fqcn']

    endif

    if (a:parentFQCN == objectManagerFQCN || phpcomplete_extended#isClassOfType(a:parentFQCN, objectManagerFQCN))
        \ && isMethod && methodPropertyText == "getRepository"
        \ && has_key(symfony_index['doctrine_data']['entities'], insideBraceText)
        return symfony_index['doctrine_data']['entities'][insideBraceText]['repository']

    endif
    return fqcn
endfunction "}}}

function! s:trim_quote(str) "{{{
    let pattern = '^[''"]\zs.*\ze[''"]$'
    return matchstr(a:str, pattern)
endfunction "}}}

function! s:get_inside_quote_menu_entries(fqcn, token_data) "{{{
    let menu_entries       = []
    let menu_candidates    = {}
    let methodPropertyText = a:token_data['methodPropertyText']
    let insideBraceText    = a:token_data['insideBraceText']

    let containerAwareFQCN   = 'Symfony\Component\DependencyInjection\ContainerAwareInterface'
    let containerFQCN        = 'Symfony\Component\DependencyInjection\ContainerInterface'
    let containerBuilderFQCN = 'Symfony\Component\DependencyInjection\ContainerBuilder'
    let controllerFQCN       = 'Symfony\Bundle\FrameworkBundle\Controller\Controller'
    let objectManagerFQCN    = 'Doctrine\Common\Persistence\ObjectManager'
    let managerRegistryFQCN  = 'Doctrine\Common\Persistence\ManagerRegistry'
    let twigEngineFQCN       = 'Symfony\Bridge\Twig\TwigEngine'
    let symfony_index        = deepcopy(s:symfony_index)
    let is_service = 0

    if ((phpcomplete_extended#isClassOfType(a:fqcn, containerAwareFQCN) 
        \ || phpcomplete_extended#isClassOfType(a:fqcn, containerFQCN))
        \   && methodPropertyText == "get"
        \)
        \ ||(phpcomplete_extended#isClassOfType(a:fqcn, containerBuilderFQCN)
        \    && (methodPropertyText == 'getDefinition' || methodPropertyText == 'hasDefinition' || methodPropertyText == 'removeDefinition')
        \ ) 

        let is_service = 1
        let menu_candidates = symfony_index['services']['public']

    elseif ((phpcomplete_extended#isClassOfType(a:fqcn, containerAwareFQCN) 
        \ || phpcomplete_extended#isClassOfType(a:fqcn, containerFQCN))
        \    && (methodPropertyText == 'hasParameter' || methodPropertyText == 'getParameter')
        \)
        \ ||(phpcomplete_extended#isClassOfType(a:fqcn, containerBuilderFQCN)
        \    && methodPropertyText == "setParameter"
        \ ) 

        let is_service = 0
        let menu_candidates = symfony_index['parameters']

    elseif ( a:fqcn == objectManagerFQCN
        \ || phpcomplete_extended#isClassOfType(a:fqcn, objectManagerFQCN)
        \ || phpcomplete_extended#isClassOfType(a:fqcn, managerRegistryFQCN)
        \ )
        \ && (methodPropertyText == "getRepository" || methodPropertyText == "getClassMetadata")

        let menu_candidates = symfony_index['doctrine_data']['entities']

    elseif phpcomplete_extended#isClassOfType(a:fqcn, controllerFQCN)
            \ && (methodPropertyText == "render" || methodPropertyText == 'renderView')

        let menu_candidates = symfony_index['templates']

    elseif phpcomplete_extended#isClassOfType(a:fqcn, twigEngineFQCN)
            \ && (methodPropertyText == "render" || methodPropertyText == 'renderView')

        let menu_candidates = symfony_index['templates']
    endif

    let menu_candidates  = filter(menu_candidates, 'v:key =~ "^' . escape(insideBraceText, '') .'"')

    return values(map(menu_candidates, "{
                \ 'word': escape(v:key, ' '),
                \ 'kind': '',
                \ 'menu': is_service? v:val.service_fqcn : '',
                \ 'info': is_service? v:val.service_fqcn : ''
                \}"
            \))
endfunction "}}}

function! phpcomplete_extended#symfony#is_valid_project() "{{{
    let is_valid = phpcomplete_extended#is_phpcomplete_extended_project() && !empty(s:symfony_index)
    if !is_valid
        echohl WarningMsg | echo  "Not a Valid Symfony project" | echohl None
    endif
    return is_valid
endfunction "}}}

function! phpcomplete_extended#symfony#get_services() "{{{
    return deepcopy(s:symfony_index['services'])
endfunction "}}}

function! phpcomplete_extended#symfony#get_tag_services() "{{{
    return deepcopy(s:symfony_index['tag_services'])
endfunction "}}}

function! phpcomplete_extended#symfony#get_routes() "{{{
    return deepcopy(s:symfony_index['routes'])
endfunction "}}}

function! phpcomplete_extended#symfony#get_bundles() "{{{
    return deepcopy(s:symfony_index['bundles'])
endfunction "}}}

function! phpcomplete_extended#symfony#get_doctrine_data() "{{{
    return deepcopy(s:symfony_index['doctrine_data'])
endfunction "}}}

let &cpo = s:save_cpo
unlet s:save_cpo

" vim: foldmethod=marker:expandtab:ts=4:sts=4:tw=78
