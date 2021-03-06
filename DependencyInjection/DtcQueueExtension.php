<?php

namespace Dtc\QueueBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader;

class DtcQueueExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $processor = new Processor();
        $configuration = new Configuration();

        $config = $processor->processConfiguration($configuration, $configs);
        $this->configBeanstalkd($config, $container);
        $this->configRabbitMQ($config, $container);

        $container->setParameter('dtc_queue.default_manager', $config['default_manager']);
        $container->setParameter('dtc_queue.document_manager', $config['document_manager']);
        $container->setParameter('dtc_queue.entity_manager', $config['entity_manager']);
        $container->setParameter('dtc_queue.class_job', isset($config['class_job']) ? $config['class_job'] : null);
        $container->setParameter('dtc_queue.class_job_archive', isset($config['class_job_archive']) ? $config['class_job_archive'] : null);
        $container->setParameter('dtc_queue.class_run', isset($config['class_run']) ? $config['class_run'] : null);
        $container->setParameter('dtc_queue.class_run_archive', isset($config['class_run_archive']) ? $config['class_run_archive'] : null);

        // Load Grid if Dtc\GridBundle Bundle is registered
        $yamlLoader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $yamlLoader->load('queue.yml');
    }

    protected function configRabbitMQ(array $config, ContainerBuilder $container)
    {
        if (isset($config['rabbit_mq'])) {
            foreach (['host', 'port', 'user', 'password'] as $value) {
                if (!isset($config['rabbit_mq'][$value])) {
                    throw new \Exception('dtc_queue: rabbit_mq must have '.$value.' in config.yml');
                }
            }
            $container->setParameter('dtc_queue.rabbit_mq', $config['rabbit_mq']);
        }
    }

    protected function configBeanstalkd(array $config, ContainerBuilder $container)
    {
        if (isset($config['beanstalkd'])) {
            if (!isset($config['beanstalkd']['host'])) {
                throw new \Exception('dtc_queue: beanstalkd requires host in config.yml');
            }
        }

        if (isset($config['beanstalkd']['host'])) {
            $container->setParameter('dtc_queue.beanstalkd.host', $config['beanstalkd']['host']);
        }
        if (isset($config['beanstalkd']['tube'])) {
            $container->setParameter('dtc_queue.beanstalkd.tube', $config['beanstalkd']['tube']);
        }
    }

    public function getAlias()
    {
        return 'dtc_queue';
    }
}
