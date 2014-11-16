<?php

namespace Haniki\TaskLoggerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ProfileFormType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('jira_url', null, array('label' => 'jira url:'))
            ->add('jira_username', null, array('label' => 'jira username:'))
            ->add('jira_password', 'password', array(
                'label' => 'jira password:',
                'required' => false
            ));
    }

    public function getParent()
    {
        return 'fos_user_profile';
    }

    public function getName()
    {
        return 'task_logger_user_profile';
    }

}
